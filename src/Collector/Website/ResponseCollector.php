<?php

namespace Startwind\Inventorio\Collector\Website;

use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Startwind\Inventorio\Collector\BasicCollector;
use Startwind\Inventorio\Collector\ClientAwareCollector;
use Startwind\Inventorio\Collector\InventoryAwareCollector;
use Startwind\Inventorio\Exec\Runner;
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
                $url = 'https://' . $domain;
                $result = $this->client->get($url);
            } catch (ClientException $e) {
                $uptimeStatus[$domain] = [
                    'code' => $e->getResponse()->getStatusCode(),
                    'h2' => $this->supportsHttp2($domain)
                ];
                continue;
            } catch (ServerException $e) {
                $uptimeStatus[$domain] = [
                    'code' => $e->getResponse()->getStatusCode(),
                    'h2' => $this->supportsHttp2($domain)
                ];
                continue;
            } catch (Exception $e) {
                $uptimeStatus[$domain] = [
                    'code' => 599,
                    'message' => $e->getMessage(),
                    'h2' => false
                ];
                continue;
            }

            $uptimeStatus[$domain] = [
                'code' => $result->getStatusCode(),
                'protocol_version' => $result->getProtocolVersion(),
                'h2' => $this->supportsHttp2($domain)
            ];
        }

        return $uptimeStatus;
    }

    private function supportsHttp2(string $host): bool
    {
        $output = Runner::getInstance()->run("echo | openssl s_client -alpn h2 -connect {$host}:443 2>/dev/null")->getOutput();
        return str_contains($output, 'ALPN protocol: h2');
    }
}
