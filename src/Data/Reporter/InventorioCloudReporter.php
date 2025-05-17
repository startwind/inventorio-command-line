<?php

namespace Startwind\Inventorio\Data\Reporter;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class InventorioCloudReporter
{
    const COLLECT_URL = 'https://collect.inventorio.cloud/collect';
    private Client $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    public function report(string $serverId, array $data)
    {
        $payload = [
            'server_id' => $serverId,
            'values' => $data
        ];

        var_dump($payload);

        $this->client->post(self::COLLECT_URL, [
            RequestOptions::JSON => $payload
        ]);
    }
}