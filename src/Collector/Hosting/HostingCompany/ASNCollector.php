<?php

namespace Startwind\Inventorio\Collector\Hosting\HostingCompany;

use Startwind\Inventorio\Collector\Collector;

class ASNCollector implements Collector
{
    public function getIdentifier(): string
    {
        return 'AutonomousSystem';
    }

    public function collect(): array
    {
        // @todo use already collected ip address
        $ip = file_get_contents('https://api.ipify.org');
        $data = json_decode(file_get_contents("http://ip-api.com/json/" . $ip), true);

        $asnArray = explode(' ', $data['as']);

        $asn = substr($asnArray[0], 2);
        $as = $data['as'];

        $short = $as;

        if (str_contains(strtolower($short), 'hetzner')) {
            $short = 'hetzner';
        }

        return [
            'as' => $as,
            'asn' => $asn,
            'short' => $short
        ];
    }
}
