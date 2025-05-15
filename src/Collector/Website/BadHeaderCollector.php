<?php

namespace Startwind\Inventorio\Collector\Website;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Startwind\Inventorio\Collector\BasicCollector;
use Startwind\Inventorio\Collector\ClientAwareCollector;
use Startwind\Inventorio\Collector\InventoryAwareCollector;
use Startwind\Inventorio\Util\Client;
use Startwind\Inventorio\Util\WebsiteUtil;

class BadHeaderCollector extends BasicCollector implements InventoryAwareCollector, ClientAwareCollector
{
    protected string $identifier = "WebsiteBadHeader";

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

        $badHeaders = [];

        foreach ($domains as $domain) {
            try {
                $url = $domain;
                if (!str_starts_with($url, 'http')) $url = 'https://' . $url;
                $response = $this->client->get($url);
            } catch (ClientException $e) {
                $response = $e->getResponse();
            } catch (ServerException $e) {
                $response = $e->getResponse();
            } catch (\Exception $e) {
                continue;
            }

            $headers = $response->getHeaders();

            if (array_key_exists('Server', $headers)) {
                if (str_contains($headers['Server'][0], 'Apache/')) {
                    $badHeaders[$domain]['apache_version'] = $headers['Server'][0];
                }
            }

            if (array_key_exists('X-Powered-By', $headers)) {
                if (str_contains($headers['X-Powered-By'][0], 'PHP/')) {
                    $badHeaders[$domain]['php_version'] = $headers['X-Powered-By'][0];
                }
            }
        }

        var_dump($badHeaders);

        return $badHeaders;
    }
}
