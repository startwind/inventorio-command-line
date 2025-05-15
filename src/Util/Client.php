<?php

namespace Startwind\Inventorio\Util;

class Client
{
    private \GuzzleHttp\Client $client;

    private array $cache = [];

    public function __construct(\GuzzleHttp\Client $client)
    {
        $this->client = $client;
    }

    public function get(string $url)
    {
        if (array_key_exists($url, $this->cache)) {
            return $this->cache[$url];
        } else {
            $this->cache[$url] = $this->client->request('GET', $url);
            return $this->cache[$url];
        }
    }
}