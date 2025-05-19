<?php

namespace Startwind\Inventorio\Collector\System\Service;

use Startwind\Inventorio\Collector\BasicCollector;
use Startwind\Inventorio\Exec\Runner;
use Symfony\Component\Console\Command\Command;

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
        $services = [];

        $output = Runner::run("systemctl list-units --type=service --all --no-legend | awk '{sub(/^● /, \"\"); print \$1}'")->getOutput();

        if (!$output) {
            return [];
        }

        $units = explode("\n", trim($output));

        foreach ($units as $unit) {
            if (empty($unit)) {
                continue;
            }

            $loadState = trim(Runner::run("systemctl show $unit --property=LoadState --value")->getOutput());
            if ($loadState !== 'loaded') {
                continue;
            }

            $id = trim(Runner::run("systemctl show $unit --property=Id --value")->getOutput());
            $description = trim(Runner::run("systemctl show $unit --property=Description --value")->getOutput());
            $activeState = trim(Runner::run("systemctl show $unit --property=ActiveState --value")->getOutput());
            $subState = trim(Runner::run("systemctl show $unit --property=SubState --value")->getOutput());

            $service = str_replace('.service', '', $id);

            $services[] = [
                'Id' => $id,
                'Description' => $description,
                'ActiveState' => $activeState,
                'SubState' => $subState,
                'SystemService' => in_array($service, $this->systemServices)
            ];
        }

        return $services;
    }

}
