<?php

namespace Startwind\Inventorio\Collector\System\Security;

use Startwind\Inventorio\Collector\BasicCollector;

class GeneralSecurityCollector extends BasicCollector
{
    protected string $identifier = 'SystemSecurity';

    public function collect(): array
    {
        $available = $this->isUnattendedUpgradesUsable();
        $enabled = false;
        if ($available) {
            $enabled = $this->isUnattendedUpgradesEnabled();
        }

        return [
            'unattendedUpgradesAvailable' => $available,
            'unattendedUpgradesEnabled' => $enabled
        ];
    }

    private function isUnattendedUpgradesEnabled(): bool
    {
        $configFile = '/etc/apt/apt.conf.d/20auto-upgrades';

        if (!file_exists($configFile)) {
            return false;
        }

        $content = file_get_contents($configFile);
        if ($content === false) {
            return false;
        }

        $updateListEnabled = preg_match('/APT::Periodic::Update-Package-Lists\s+"1";/', $content);
        $unattendedUpgradeEnabled = preg_match('/APT::Periodic::Unattended-Upgrade\s+"1";/', $content);

        return $updateListEnabled && $unattendedUpgradeEnabled;
    }

    private function isUnattendedUpgradesUsable(): bool
    {
        if (stripos(PHP_OS, 'Linux') === false) {
            return false;
        }

        $whichApt = trim(shell_exec('which apt'));
        if (empty($whichApt)) {
            return false;
        }

        return !empty(shell_exec('apt-cache show unattended-upgrades 2>/dev/null'));
    }
}
