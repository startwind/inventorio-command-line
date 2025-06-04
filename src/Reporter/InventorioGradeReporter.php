<?php

namespace Startwind\Inventorio\Reporter;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\RequestOptions;
use RuntimeException;
use Symfony\Component\Console\Helper\Table;
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
            throw new RuntimeException('Unable to connect to ' . $endpoint . '. Message: ' . $e->getMessage());
        } catch (ServerException $e) {
            throw new RuntimeException('Unable to connect to ' . $endpoint . ' (ServerException). Message: ' . $e->getMessage());
        } catch (Exception $e) {
            // var_dump($e->getMessage());
            throw $e;
        }

        // var_dump((string)$response->getBody());die;

        $result = json_decode((string)$response->getBody(), true);

        if (!is_array($result) || !array_key_exists('status', $result)) {
            throw new RuntimeException('Unknown error.');
        }

        if ($result['status'] !== 'SUCCESS') {
            throw new RuntimeException($result['message']);
        }

        $table = new Table($this->output);
        $table->setHeaders(['Name', 'Description', 'Assets']);

        foreach ($result['data']['hints'] as $hint) {
            $row = [
                'name' => $hint['definition']['name'],
                'description' => wordwrap($hint['definition']['description'], 40)
            ];

            $assets = [];

            if (array_key_exists('files', $hint['issue']['parameters'])) {
                $assets = array_keys($hint['issue']['parameters']['files']);
            }

            if (array_key_exists('webistes', $hint['issue']['parameters'])) {
                $assets = $hint['issue']['parameters']['files'];
            }

            $row['assets'] = implode("\n", $assets);

            $table->addRow($row);
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
