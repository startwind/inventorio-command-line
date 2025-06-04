<?php

namespace Startwind\Inventorio\Reporter;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\RequestOptions;
use RuntimeException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Send the collected dato to the Inventorio Cloud.
 */
class InventorioGradeReporter implements Reporter
{
    private const ENDPOINT_COLLECT = '/grade/';

    private OutputInterface $output;
    private string $userId;
    private string $serverId;
    private string $inventorioServer;

    const SEVERITIES = [
        0 => '<fg=white;bg=blue> low </>',
        500 => '<fg=white;bg=yellow> medium </>',
        1000 => '<fg=white;bg=red> high </>',
    ];

    public function __construct(OutputInterface $output, string $inventorioServer, string $serverId, string $userId)
    {
        $this->output = $output;
        $this->userId = $userId;
        $this->serverId = $serverId;
        $this->inventorioServer = $inventorioServer;
    }

    /**
     * @inheritDoc
     */
    public function report(array $collectionData): void
    {
        $endpoint = $this->getPreparedEndpoint();

        $client = new Client();

        $payload = [
            'userId' => $this->userId,
            'data' => $collectionData
        ];

        try {
            $response = $client->post($endpoint, [
                RequestOptions::JSON => $payload,
                RequestOptions::TIMEOUT => 5,
                RequestOptions::CONNECT_TIMEOUT => 2
            ]);
        } catch (ConnectException $e) {
            var_dump('oosssahh');
            throw new RuntimeException('Unable to connect to ' . $endpoint . '. Message: ' . $e->getMessage());
        } catch (ServerException $e) {
            var_dump($e->getResponse()->getBody()->getContents());
            var_dump('ooahh');
            throw new RuntimeException('Unable to connect to ' . $endpoint . ' (ServerException). Message: ' . $e->getMessage());
        } catch (Exception $e) {
            // var_dump($e->getMessage());
            var_dump('ahh');
            throw $e;
        }

        $result = json_decode((string)$response->getBody(), true);

        if (!is_array($result) || !array_key_exists('status', $result)) {
            throw new RuntimeException('Unknown error.');
        }

        if ($result['status'] !== 'SUCCESS') {
            throw new RuntimeException($result['message']);
        }

        $table = new Table($this->output);
        $table->setHeaders(['Severity', 'Name', 'Description', 'Assets']);

        $hints = $result['data']['hints'];
        $lastIndex = count($hints) - 1;

        foreach ($hints as $i => $hint) {
            $row = [
                'severity' => self::SEVERITIES[$hint['definition']['severity']],
                'name' => $hint['definition']['name'],
                'description' => wordwrap($hint['definition']['description'], 40)
            ];

            $assets = [];

            if (array_key_exists('files', $hint['issue']['parameters'])) {
                $assets = array_keys($hint['issue']['parameters']['files']);
                foreach ($assets as $key => $asset) {
                    $assets[$key] = '- ' . $asset;
                }
            }

            if (array_key_exists('websites', $hint['issue']['parameters'])) {
                $assets = $hint['issue']['parameters']['websites'];
                foreach ($assets as $key => $asset) {
                    $assets[$key] = '- https://' . $asset;
                }
            }

            $row['assets'] = implode("\n", $assets);

            $table->addRow($row);

            if ($i < $lastIndex) {
                $table->addRow(new TableSeparator());
            }
        }

        $table->render();
    }

    /**
     * Return the final endpoint where the collected data should be sent to.
     */
    private function getPreparedEndpoint(): string
    {
        return str_replace('{serverId}', $this->serverId, $this->inventorioServer . self::ENDPOINT_COLLECT);
    }
}
