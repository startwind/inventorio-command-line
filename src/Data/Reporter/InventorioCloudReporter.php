<?php

namespace Startwind\Inventorio\Data\Reporter;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;

class InventorioCloudReporter
{
    const COLLECT_URL = 'https://collect.inventorio.cloud/collect/index.php';
    private Client $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    public function report(string $serverId, array $data): void
    {
        $payload = [
            'server_id' => $serverId,
            'values' => $data
        ];

        try {
            $this->client->post(self::COLLECT_URL, [
                RequestOptions::JSON => $payload
            ]);
        } catch (ClientException $exception) {
            var_dump('ce: ' . (string)$exception->getResponse()->getBody());
        } catch (RequestException $exception) {
            var_dump('re: ' . (string)$exception->getResponse()->getBody());
        }
    }
}