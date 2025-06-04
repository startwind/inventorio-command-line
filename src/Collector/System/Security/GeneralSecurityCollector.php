<?php

namespace Startwind\Inventorio\Collector\System\Security;

use Startwind\Inventorio\Collector\BasicCollector;
use Startwind\Inventorio\Exec\Runner;

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

        $result = [
            'unattendedUpgradesAvailable' => $available,
            'unattendedUpgradesEnabled' => $enabled,
            'sshKeyOnlyAvailable' => $sshOnlyConfig['supported'],
            'sshKeyOnlyEnabled' => $sshOnlyConfig['enforced'],
        ];

        var_dump($result);

        return $result;
    }

    private function isUnattendedUpgradesEnabled(): bool
    {
        $configFile = '/etc/apt/apt.conf.d/20auto-upgrades';

        $runner = Runner::getInstance();

        if (!$runner->fileExists($configFile)) {
            return false;
        }

        $content = $runner->getFileContents($configFile);

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

        $runner = Runner::getInstance();

        if (!$runner->commandExists('apt')) return false;

        return !empty($runner->run('apt-cache show unattended-upgrades 2>/dev/null')->getOutput());
    }

    function checkSshKeyOnlyLogin(): array
    {
        $result = ['supported' => false, 'enforced' => false];

        if (stripos(PHP_OS, 'Linux') === false) {
            return $result;
        }

        $runner = Runner::getInstance();

        $configPath = '/etc/ssh/sshd_config';
        if (!$runner->fileExists($configPath)) {
            return $result;
        }

        $result['supported'] = true;

        $config = $runner->getFileContents($configPath);
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
