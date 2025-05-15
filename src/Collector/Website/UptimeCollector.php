<?php

namespace Startwind\Inventorio\Collector\Website;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Startwind\Inventorio\Collector\BasicCollector;
use Startwind\Inventorio\Collector\ClientAwareCollector;
use Startwind\Inventorio\Collector\InventoryAwareCollector;
use Startwind\Inventorio\Util\Client;
use Startwind\Inventorio\Util\WebsiteUtil;

class UptimeCollector extends BasicCollector implements InventoryAwareCollector, ClientAwareCollector
{
    protected string $identifier = "WebsiteUptime";

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

        $uptimeStatus = [];

        foreach ($domains as $domain) {
            try {
                $result = $this->client->get('https://' . $domain);
            } catch (ClientException $e) {
                $uptimeStatus[$domain] = ['code' => $e->getResponse()->getStatusCode()];
                continue;
            } catch (ServerException $e) {
                $uptimeStatus[$domain] = ['code' => $e->getResponse()->getStatusCode()];
                continue;
            } catch (\Exception $e) {
                $uptimeStatus[$domain] = ['code' => 599, 'message' => $e->getMessage()];
                continue;
            }

            $uptimeStatus[$domain] = ['code' => $result->getStatusCode()];
        }

        return $uptimeStatus;
    }
}
