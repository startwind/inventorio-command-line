<?php

namespace Startwind\Inventorio\Collector\Website;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Startwind\Inventorio\Collector\BasicCollector;
use Startwind\Inventorio\Collector\ClientAwareCollector;
use Startwind\Inventorio\Collector\InventoryAwareCollector;
use Startwind\Inventorio\Util\Client;
use Startwind\Inventorio\Util\WebsiteUtil;

class HeaderCollector extends BasicCollector implements InventoryAwareCollector, ClientAwareCollector
{
    protected string $identifier = "WebsiteHeader";

    private array $inventory;
    private Client $client;

    public function setClient(Client $client): void
    {
        $this->client = $client;
    }

    public function setInventory(array $inventory): void
    {
        $this->inventory = $inventory;
    }

    public function collect(): array
    {
        $domains = WebsiteUtil::extractDomains($this->inventory);

        $headers = [];

        foreach ($domains as $domain) {
            try {
                $url = $domain;
                if (!str_starts_with($url, 'http')) $url = 'https://' . $url;
                $response = $this->client->get($url);
            } catch (ClientException $e) {
                $response = $e->getResponse();
            } catch (ServerException $e) {
                $response = $e->getResponse();
            } catch (\Exception) {
                continue;
            }

            $headers[$domain] = $response->getHeaders();
        }

        return $headers;
    }
}
