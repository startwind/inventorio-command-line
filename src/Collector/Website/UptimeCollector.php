<?php

namespace Startwind\Inventorio\Collector\Website;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Startwind\Inventorio\Collector\Application\WebServer\Apache\ApacheServerNameCollector;
use Startwind\Inventorio\Collector\BasicCollector;
use Startwind\Inventorio\Collector\InventoryAwareCollector;

class UptimeCollector extends BasicCollector implements InventoryAwareCollector
{
    protected string $identifier = "WebsiteUptime";

    private array $inventory;

    public function setInventory(array $inventory): void
    {
        $this->inventory = $inventory;
    }

    public function collect(): array
    {
        if (!array_key_exists(ApacheServerNameCollector::COLLECTION_IDENTIFIER, $this->inventory)
            || !is_array($this->inventory[ApacheServerNameCollector::COLLECTION_IDENTIFIER])
        ) return [];

        $configs = $this->inventory[ApacheServerNameCollector::COLLECTION_IDENTIFIER];

        $uptimeStatus = [];

        $client = new Client();

        foreach ($configs as $config) {
            $domain = $config[ApacheServerNameCollector::FIELD_SERVER_NAME];
            try {
                $result = $client->get('https://' . $domain);
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
