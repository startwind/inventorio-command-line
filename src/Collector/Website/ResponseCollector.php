<?php

namespace Startwind\Inventorio\Collector\Website;

use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Startwind\Inventorio\Collector\BasicCollector;
use Startwind\Inventorio\Collector\ClientAwareCollector;
use Startwind\Inventorio\Collector\InventoryAwareCollector;
use Startwind\Inventorio\Util\Client;
use Startwind\Inventorio\Util\WebsiteUtil;

class ResponseCollector extends BasicCollector implements InventoryAwareCollector, ClientAwareCollector
{
    protected string $identifier = "WebsiteResponse";

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
                $uptimeStatus[$domain] = [
                    'code' => $e->getResponse()->getStatusCode(),
                    'protocol_version' => $e->getResponse()->getProtocolVersion(),
                ];
                continue;
            } catch (ServerException $e) {
                $uptimeStatus[$domain] = [
                    'code' => $e->getResponse()->getStatusCode(),
                    'protocol_version' => $e->getResponse()->getProtocolVersion()
                ];
                continue;
            } catch (Exception $e) {
                $uptimeStatus[$domain] = [
                    'code' => 599,
                    'message' => $e->getMessage(),
                    'protocol_version' => false
                ];
                continue;
            }

            $uptimeStatus[$domain] = [
                'code' => $result->getStatusCode(),
                'protocol_version' => $result->getProtocolVersion()
            ];
        }

        return $uptimeStatus;
    }
}
