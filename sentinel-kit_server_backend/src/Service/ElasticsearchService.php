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
    public function rawQuery(array $queryData): array
    {
        try {
            $index = $queryData['index'] ?? 'sentinelkit-*';
            
            $bodyData = $queryData;
            unset($bodyData['index']);
            
            $params = [
                'index' => $index,
                'body' => $bodyData
            ];

            $this->logger->info('Elasticsearch raw query execution', [
                'index' => $index,
                'query_keys' => array_keys($bodyData)
            ]);
            
            $response = $this->client->search($params);
            return $response->asArray();
        } catch (\Exception $e) {
            $this->logger->error('Elasticsearch raw query error', [
                'error' => $e->getMessage(),
                'query' => $queryData
            ]);
            throw $e;
        }
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * Get information about indices matching a pattern
     */
    public function getIndicesInfo(string $pattern): array
    {
        try {
            $this->logger->info('Fetching indices info', ['pattern' => $pattern]);
            
            // First try to get datastreams info
            $datastreams = [];
            try {
                $datastreamResponse = $this->client->indices()->getDataStream(['name' => $pattern]);
                $datastreams = $datastreamResponse->asArray()['data_streams'] ?? [];
                $this->logger->info('Found datastreams', ['count' => count($datastreams)]);
            } catch (\Exception $e) {
                $this->logger->info('No datastreams found or datastreams not supported', ['error' => $e->getMessage()]);
            }
            
            // Get indices stats for both regular indices and backing indices of datastreams
            $response = $this->client->indices()->stats([
                'index' => $pattern,
                'metric' => ['docs', 'store'],
                'ignore_unavailable' => true
            ]);
            
            $indices = $response->asArray()['indices'] ?? [];
            $this->logger->info('Found indices', ['count' => count($indices)]);
            
            // Get indices health
            $healthData = [];
            try {
                $healthResponse = $this->client->cat()->indices([
                    'index' => $pattern,
                    'format' => 'json',
                    'h' => ['index', 'health'],
                    'ignore_unavailable' => true
                ]);
                
                $healthArray = $healthResponse->asArray();
                if (is_array($healthArray)) {
                    foreach ($healthArray as $item) {
                        if (isset($item['index']) && isset($item['health'])) {
                            $healthData[$item['index']] = $item['health'];
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->logger->warning('Could not fetch health data', ['error' => $e->getMessage()]);
            }
            
            // Combine stats and health information
            $result = [];
            foreach ($indices as $indexName => $stats) {
                $result[$indexName] = [
                    'docs' => $stats['total']['docs'] ?? ['count' => 0],
                    'store' => $stats['total']['store'] ?? ['size_in_bytes' => 0],
                    'health' => $healthData[$indexName] ?? 'unknown'
                ];
            }
            
            $this->logger->info('Processed indices info', ['result_count' => count($result)]);
            return $result;
            
        } catch (\Exception $e) {
            $this->logger->error('Error getting indices info', [
                'error' => $e->getMessage(),
                'pattern' => $pattern,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Get ingestion statistics for a datasource over time
     */
    public function getIngestionStats(string $datasourceKey, string $timeRange): array
    {
        try {
            $this->logger->info('Getting ingestion stats', ['datasourceKey' => $datasourceKey, 'timeRange' => $timeRange]);
            
            // Convert time range to Elasticsearch format
            $now = new \DateTime();
            switch ($timeRange) {
                case '24h':
                    $from = (clone $now)->sub(new \DateInterval('P1D'));
                    $interval = '1h';
                    break;
                case '7d':
                    $from = (clone $now)->sub(new \DateInterval('P7D'));
                    $interval = '1d';
                    break;
                case '30d':
                    $from = (clone $now)->sub(new \DateInterval('P30D'));
                    $interval = '1d';
                    break;
                default:
                    $from = (clone $now)->sub(new \DateInterval('P7D'));
                    $interval = '1d';
            }

            // Create index pattern directly from datasource key
            $indexPattern = 'sentinelkit-' . $datasourceKey . '-*';
            
            $this->logger->info('Using index pattern for ingestion stats', [
                'datasource_key' => $datasourceKey,
                'pattern' => $indexPattern
            ]);
            
            $params = [
                'index' => $indexPattern,
                'body' => [
                    'query' => [
                        'range' => [
                            '@timestamp' => [
                                'gte' => $from->format('c'),
                                'lte' => $now->format('c')
                            ]
                        ]
                    ],
                    'aggs' => [
                        'ingestion_over_time' => [
                            'date_histogram' => [
                                'field' => '@timestamp',
                                'fixed_interval' => $interval,
                                'time_zone' => 'UTC',
                                'min_doc_count' => 0,
                                'extended_bounds' => [
                                    'min' => $from->format('c'),
                                    'max' => $now->format('c')
                                ]
                            ]
                        ]
                    ],
                    'size' => 0
                ],
                'ignore_unavailable' => true
            ];

            $response = $this->client->search($params);
            $data = $response->asArray();
            
            $result = [];
            if (isset($data['aggregations']['ingestion_over_time']['buckets'])) {
                foreach ($data['aggregations']['ingestion_over_time']['buckets'] as $bucket) {
                    $result[] = [
                        'timestamp' => date('c', $bucket['key'] / 1000),
                        'count' => $bucket['doc_count'],
                        'sizeBytes' => $bucket['doc_count'] * 2048 // Estimate 2KB per document
                    ];
                }
            }
            
            $this->logger->info('Ingestion stats result', ['count' => count($result)]);
            return $result;
            
        } catch (\Exception $e) {
            $this->logger->error('Error getting ingestion stats', [
                'error' => $e->getMessage(),
                'datasourceKey' => $datasourceKey,
                'timeRange' => $timeRange,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Get events for a specific datasource
     */
    public function getDatasourceEvents(string $datasourceKey, int $page, int $pageSize, ?string $startDate = null, ?string $endDate = null): array
    {
        try {
            $this->logger->info('Getting datasource events', [
                'datasourceKey' => $datasourceKey, 
                'page' => $page, 
                'pageSize' => $pageSize
            ]);
            
            // Create index pattern directly from datasource key
            $indexPattern = 'sentinelkit-' . $datasourceKey . '-*';
            $from = ($page - 1) * $pageSize;
            
            $this->logger->info('Using index pattern for events', [
                'datasource_key' => $datasourceKey, 
                'pattern' => $indexPattern
            ]);
            
            $query = ['match_all' => new \stdClass()];
            
            if ($startDate && $endDate) {
                $query = [
                    'range' => [
                        '@timestamp' => [
                            'gte' => $startDate,
                            'lte' => $endDate
                        ]
                    ]
                ];
            }
            
            $params = [
                'index' => $indexPattern,
                'body' => [
                    'query' => $query,
                    'sort' => [
                        '@timestamp' => ['order' => 'desc']
                    ],
                    'from' => $from,
                    'size' => $pageSize
                ],
                'ignore_unavailable' => true
            ];

            $response = $this->client->search($params);
            $data = $response->asArray();
            
            $total = $data['hits']['total']['value'] ?? 0;
            $totalPages = ceil($total / $pageSize);
            
            $this->logger->info('Events result', [
                'total' => $total, 
                'totalPages' => $totalPages,
                'events_count' => count($data['hits']['hits'] ?? [])
            ]);
            
            return [
                'events' => $data['hits']['hits'] ?? [],
                'total' => $total,
                'page' => $page,
                'pageSize' => $pageSize,
                'totalPages' => $totalPages
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Error getting datasource events', [
                'error' => $e->getMessage(),
                'datasourceKey' => $datasourceKey,
                'page' => $page,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}