<?php

namespace Startwind\Inventorio\Collector\System\Service;

use Startwind\Inventorio\Collector\BasicCollector;
use Startwind\Inventorio\Exec\Runner;

class SystemDCollector extends BasicCollector
{
    protected string $identifier = 'ServerServiceSystemD';

    private array $systemServices = [
        'accounts-daemon',
        'console-setup',
        'cron',
        'dbus',
        'keyboard-setup',
        'systemd-journal-flush',
        'systemd-journald',
        'systemd-logind',
        'systemd-modules-load',
        'systemd-networkd-wait-online',
        'systemd-networkd',
        'systemd-quotacheck',
        'systemd-random-seed',
        'systemd-remount-fs',
        'systemd-resolved',
        'systemd-sysctl',
        'systemd-sysusers',
        'systemd-timesyncd',
        'systemd-tmpfiles-setup-dev',
        'systemd-tmpfiles-setup',
        'systemd-udev-trigger',
        'systemd-udevd',
        'systemd-update-utmp',
        'systemd-user-sessions',
        'ubuntu-fan',
        'user-runtime-dir@0',
        'user@0',
        'console-setup',
        'cron',
        'dbus',
        'finalrd',
        'keyboard-setup',
        'qemu-guest-agent',
        'rsyslog',
        'ssh',
        'systemd-binfmt',
        'systemd-tmpfiles-setup-dev-early',
        'snapd.seeded',
        'polkit',
        'multipathd',
        'finalrd',
        'cloud-init-local',
        'cloud-final',
        'cloud-config',
        'apport',
        'kmod-static-nodes',
        'multipathd',
        'plymouth-quit-wait',
        'plymouth-quit',
        'plymouth-read-write',
        'serial-getty@ttyS0',
        'setvtrgb',
        'sysstat',
        'blk-availability',
        'getty@tty1',
    ];

    public function collect(): array
    {
        $services = $this->getServices();
        return ['services' => $services];
    }

    private function getServices(): array
    {
        $runner = Runner::getInstance();

        if (!$runner->commandExists('systemctl')) {
            return [];
        }

        $output = $runner->run("systemctl show --type=service --all --no-page --property=Id,Description,LoadState,ActiveState,SubState")->getOutput();

        if (!$output) {
            return $this->getServiceClassic();
        }

        $services = [];
        $systemServices = array_flip($this->systemServices);
        $blocks = preg_split('/\n(?=Id=)/', trim($output));

        foreach ($blocks as $block) {
            $lines = explode("\n", trim($block));
            $data = [];

            foreach ($lines as $line) {
                [$key, $value] = explode('=', $line, 2);
                $data[$key] = trim($value);
            }

            if (($data['LoadState'] ?? '') !== 'loaded') {
                continue;
            }

            $id = $data['Id'] ?? '';
            $service = str_replace('.service', '', $id);

            $services[$id] = [
                'Id' => $id,
                'Description' => $data['Description'] ?? '',
                'ActiveState' => $data['ActiveState'] ?? '',
                'SubState' => $data['SubState'] ?? '',
                'SystemService' => isset($systemServices[$service])
            ];
        }

        return $services;
    }

    function getServiceClassic()
    {
        $command = "systemctl list-units --type=service --all --no-legend --no-pager | awk '{printf \"%s|%s|%s|%s|\", \$1, \$2, \$3, \$4; for (i=5; i<=NF; i++) printf \$i \" \"; print \"\"}'";

        $output = shell_exec($command);
        if ($output === null) return [];

        $lines = explode("\n", trim($output));
        $services = [];

        foreach ($lines as $line) {
            if (empty($line)) continue;
            $parts = explode('|', $line);

            if (count($parts) >= 5) {
                $id = trim($parts[0]);
                $service = str_replace('.service', '', $id);

                $services[$id] = [
                    'Id' => $service,
                    'Description' => trim($parts[4]),
                    'ActiveState' => trim($parts[2]),
                    'SubState' => trim($parts[3]),
                    'SystemService' => isset($systemServices[$service])
                ];
            }
        }

        return $services;
    }
}
