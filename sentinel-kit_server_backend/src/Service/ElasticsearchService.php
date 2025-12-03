<?php

namespace App\Service;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Psr\Log\LoggerInterface;

class ElasticsearchService
{
    private Client $client;
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        
        $hosts = [
            'https://' . ($_ENV['ELASTICSEARCH_HOST'] ?? 'sentinel-kit-db-elasticsearch-es01:9200')
        ];
        
        $clientBuilder = ClientBuilder::create()
            ->setHosts($hosts)
            ->setSSLVerification(false);
            
        if (isset($_ENV['ELASTICSEARCH_USERNAME']) && isset($_ENV['ELASTICSEARCH_PASSWORD'])) {
            $clientBuilder->setBasicAuthentication(
                $_ENV['ELASTICSEARCH_USERNAME'],
                $_ENV['ELASTICSEARCH_PASSWORD']
            );
        }
        
        $this->client = $clientBuilder->build();
    }

    public function search(array $params): array
    {
        try {
            $this->logger->info('Elasticsearch search query', [
                'index' => $params['index'] ?? 'unknown',
                'query' => $params['body']['query'] ?? null
            ]);
            
            $response = $this->client->search($params);
            return $response->asArray();
        } catch (\Exception $e) {
            $this->logger->error('Elasticsearch search error', [
                'error' => $e->getMessage(),
                'params' => $params
            ]);
            throw $e;
        }
    }

    public function count(array $params): int
    {
        try {
            $response = $this->client->count($params);
            return $response->asArray()['count'] ?? 0;
        } catch (\Exception $e) {
            $this->logger->error('Elasticsearch count error', [
                'error' => $e->getMessage(),
                'params' => $params
            ]);
            return 0;
        }
    }

    public function indexExists(string $index): bool
    {
        try {
            return $this->client->indices()->exists(['index' => $index])->asBool();
        } catch (\Exception $e) {
            $this->logger->error('Elasticsearch index exists check error', [
                'error' => $e->getMessage(),
                'index' => $index
            ]);
            return false;
        }
    }

    public function getClusterHealth(): array
    {
        try {
            $response = $this->client->cluster()->health();
            return $response->asArray();
        } catch (\Exception $e) {
            $this->logger->error('Elasticsearch cluster health error', [
                'error' => $e->getMessage()
            ]);
            return ['status' => 'unavailable'];
        }
    }

    public function getClient(): Client
    {
        return $this->client;
    }
}