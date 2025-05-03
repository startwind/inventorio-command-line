<?php

namespace Startwind\Inventorio\Reporter;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\RequestOptions;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Send the collected dato to the Inventorio Cloud.
 */
class InventorioReporter implements Reporter
{
    private const ENDPOINT_COLLECT = '/inventory/collect/{serverId}';

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
            $response = $client->put($endpoint, [
                RequestOptions::JSON => $payload
            ]);
        } catch (ConnectException $e) {
            throw new \RuntimeException('Unable to connect to ' . $endpoint . '. Message: ' . $e->getMessage());
        } catch (ServerException $e) {
            throw new \RuntimeException('Unable to connect to ' . $endpoint . ' (ServerException). Message: ' . $e->getMessage());
        }

         var_dump((string)$response->getBody());die;

        $result = json_decode((string)$response->getBody(), true);

        if (!is_array($result) || !array_key_exists('status', $result)) {
            throw new \RuntimeException('Unknown error.');
        }

        if ($result['status'] !== 'SUCCESS') {
            throw new \RuntimeException($result['message']);
        }

        $this->output->writeln('<info>Data successfully sent to Inventorio Cloud.</info>');
    }

    /**
     * Return the final endpoint where the collected data should be sent to.
     */
    private function getPreparedEndpoint(): string
    {
        return str_replace('{serverId}', $this->serverId, $this->inventorioServer . self::ENDPOINT_COLLECT);
    }
}
