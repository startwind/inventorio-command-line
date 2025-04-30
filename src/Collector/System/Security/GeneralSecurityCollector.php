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

        $sshOnlyConfig = $this->checkSshKeyOnlyLogin();

        return [
            'unattendedUpgradesAvailable' => $available,
            'unattendedUpgradesEnabled' => $enabled,
            'sshKeyOnlyAvailable' => $sshOnlyConfig['supported'],
            'sshKeyOnlyEnabled' => $sshOnlyConfig['enforced'],
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

    function checkSshKeyOnlyLogin(): array
    {
        $result = ['supported' => false, 'enforced' => false];

        if (stripos(PHP_OS, 'Linux') === false) {
            return $result;
        }

        $configPath = '/etc/ssh/sshd_config';
        if (!file_exists($configPath)) {
            return $result;
        }

        $result['supported'] = true;

        $config = file_get_contents($configPath);
        if ($config === false) {
            return $result;
        }

        $lines = preg_split('/\r\n|\r|\n/', $config);
        $settings = [];

        foreach ($lines as $line) {
            $line = trim(preg_replace('/#.*/', '', $line)); // Kommentare entfernen
            if ($line === '') continue;

            if (preg_match('/^\s*(\w+)\s+(yes|no)\s*$/i', $line, $matches)) {
                $key = strtolower($matches[1]);
                $value = strtolower($matches[2]);
                $settings[$key] = $value;
            }
        }

        $passwordOff = isset($settings['passwordauthentication']) && $settings['passwordauthentication'] === 'no';
        $challengeOff = isset($settings['challengeresponseauthentication']) && $settings['challengeresponseauthentication'] === 'no';
        $usePamOff = isset($settings['usepam']) && $settings['usepam'] === 'no';

        $result['enforced'] = $passwordOff && $challengeOff && $usePamOff;

        return $result;
    }
}
