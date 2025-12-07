<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ElasticsearchService;
use Psr\Log\LoggerInterface;

#[Route('/api/datasources', name: 'api_datasources_')]
class DatasourcesController extends AbstractController
{
    private ElasticsearchService $elasticsearchService;
    private LoggerInterface $logger;

    public function __construct(ElasticsearchService $elasticsearchService, LoggerInterface $logger)
    {
        $this->elasticsearchService = $elasticsearchService;
        $this->logger = $logger;
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function getDatasources(Request $request): JsonResponse
    {
        try {
            $indices = $this->elasticsearchService->getIndicesInfo('sentinelkit-*');
            
            $this->logger->info('Processing indices for datasources', [
                'indices_count' => count($indices),
                'indices' => array_keys($indices)
            ]);
            
            $datasources = [];
            foreach ($indices as $indexName => $indexInfo) {
                // Extract datasource name from index pattern
                $datasourceName = $this->extractDatasourceName($indexName);
                
                $this->logger->debug('Processing index', [
                    'index' => $indexName,
                    'extracted_datasource' => $datasourceName
                ]);
                
                if (!isset($datasources[$datasourceName])) {
                    $datasources[$datasourceName] = [
                        'name' => $datasourceName,
                        'key' => $this->extractDatasourceKey($indexName), // Original key for API calls
                        'indices' => [],
                        'totalDocuments' => 0,
                        'totalSizeBytes' => 0,
                        'status' => 'active'
                    ];
                }
                
                $datasources[$datasourceName]['indices'][] = [
                    'name' => $indexName,
                    'documents' => $indexInfo['docs']['count'] ?? 0,
                    'sizeBytes' => $indexInfo['store']['size_in_bytes'] ?? 0,
                    'health' => $indexInfo['health'] ?? 'unknown'
                ];
                
                $datasources[$datasourceName]['totalDocuments'] += $indexInfo['docs']['count'] ?? 0;
                $datasources[$datasourceName]['totalSizeBytes'] += $indexInfo['store']['size_in_bytes'] ?? 0;
                
                // Set status based on health
                $health = $indexInfo['health'] ?? 'unknown';
                if ($health === 'red') {
                    $datasources[$datasourceName]['status'] = 'error';
                } elseif ($health === 'yellow' && $datasources[$datasourceName]['status'] !== 'error') {
                    $datasources[$datasourceName]['status'] = 'warning';
                }
            }

            $this->logger->info('Final datasources grouping', [
                'datasources_count' => count($datasources),
                'datasources' => array_keys($datasources)
            ]);

            return new JsonResponse(array_values($datasources));
            
        } catch (\Exception $e) {
            $this->logger->error('Error fetching datasources from Elasticsearch', [
                'error' => $e->getMessage()
            ]);
            
            // Return empty array instead of mock data to see real connection issues
            return new JsonResponse([], 500);
        }
    }

    #[Route('/{datasource}/ingestion-stats', name: 'ingestion_stats', methods: ['GET'])]
    public function getIngestionStats(Request $request, string $datasource): JsonResponse
    {
        $timeRange = $request->query->get('timeRange', '7d'); // 24h, 7d, 30d
        
        try {
            // Find the datasource key by searching all datasources
            $datasourceKey = $this->findDatasourceKey($datasource);
            if (!$datasourceKey) {
                return new JsonResponse([
                    'error' => "Datasource '{$datasource}' not found"
                ], 404);
            }
            
            $stats = $this->elasticsearchService->getIngestionStats($datasourceKey, $timeRange);
            return new JsonResponse([
                'timeRange' => $timeRange,
                'datasource' => $datasource,
                'data' => $stats
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Error fetching ingestion stats from Elasticsearch', [
                'error' => $e->getMessage(),
                'datasource' => $datasource,
                'timeRange' => $timeRange
            ]);
            
            // Return empty data with error status
            return new JsonResponse([
                'timeRange' => $timeRange,
                'datasource' => $datasource,
                'data' => [],
                'error' => 'Failed to fetch ingestion statistics'
            ], 500);
        }
    }

    #[Route('/{datasource}/events', name: 'events', methods: ['GET'])]
    public function getDatasourceEvents(Request $request, string $datasource): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $pageSize = $request->query->getInt('pageSize', 50);
        $startDate = $request->query->get('startDate');
        $endDate = $request->query->get('endDate');
        
        try {
            // Find the datasource key by searching all datasources
            $datasourceKey = $this->findDatasourceKey($datasource);
            if (!$datasourceKey) {
                return new JsonResponse([
                    'error' => "Datasource '{$datasource}' not found"
                ], 404);
            }
            
            $events = $this->elasticsearchService->getDatasourceEvents(
                $datasourceKey, 
                $page, 
                $pageSize, 
                $startDate, 
                $endDate
            );
            
            return new JsonResponse($events);
            
        } catch (\Exception $e) {
            $this->logger->error('Error fetching events from Elasticsearch', [
                'error' => $e->getMessage(),
                'datasource' => $datasource,
                'page' => $page
            ]);
            
            // Return empty events with error status
            return new JsonResponse([
                'events' => [],
                'total' => 0,
                'page' => $page,
                'pageSize' => $pageSize,
                'totalPages' => 0,
                'error' => 'Failed to fetch events'
            ], 500);
        }
    }

    private function extractDatasourceName(string $indexName): string
    {
        $this->logger->info("Extracting datasource name from index: {$indexName}");
        
        // Handle datastream pattern: .ds-sentinelkit-{datasource}-{yyyy.mm.dd}-{yyyy.mm.dd}-{suffix}
        if (preg_match('/^\.ds-sentinelkit-(.+?)-\d{4}\.\d{2}\.\d{2}-\d{4}\.\d{2}\.\d{2}-\d+$/', $indexName, $matches)) {
            $datasourceName = ucwords(str_replace('-', ' ', $matches[1]));
            $this->logger->info("Datastream pattern matched. Extracted datasource: {$datasourceName}");
            return $datasourceName;
        }
        
        // Handle legacy pattern: sentinelkit-{datasource}-{yyyy.mm.dd}
        if (preg_match('/^sentinelkit-(.+?)-\d{4}\.\d{2}\.\d{2}$/', $indexName, $matches)) {
            $datasourceName = ucwords(str_replace('-', ' ', $matches[1]));
            $this->logger->info("Legacy pattern matched. Extracted datasource: {$datasourceName}");
            return $datasourceName;
        }
        
        // Fallback: return the original index name
        $this->logger->warning("No pattern matched for index: {$indexName}. Using fallback.");
        return $indexName;
    }

    private function extractDatasourceKey(string $indexName): string
    {
        // Handle datastream pattern: .ds-sentinelkit-{datasource}-{yyyy.mm.dd}-{yyyy.mm.dd}-{suffix}
        if (preg_match('/^\.ds-sentinelkit-(.+?)-\d{4}\.\d{2}\.\d{2}-\d{4}\.\d{2}\.\d{2}-\d+$/', $indexName, $matches)) {
            return $matches[1]; // Return original key without formatting
        }
        
        // Handle legacy pattern: sentinelkit-{datasource}-{yyyy.mm.dd}
        if (preg_match('/^sentinelkit-(.+?)-\d{4}\.\d{2}\.\d{2}$/', $indexName, $matches)) {
            return $matches[1]; // Return original key without formatting
        }
        
        // Fallback: return the original index name
        return $indexName;
    }

    private function findDatasourceKey(string $datasourceName): ?string
    {
        try {
            $indices = $this->elasticsearchService->getIndicesInfo('sentinelkit-*');
            
            foreach ($indices as $indexName => $indexInfo) {
                $extractedName = $this->extractDatasourceName($indexName);
                if ($extractedName === $datasourceName) {
                    $key = $this->extractDatasourceKey($indexName);
                    $this->logger->info("Found datasource key for '{$datasourceName}': '{$key}'");
                    return $key;
                }
            }
            
            $this->logger->warning("No datasource key found for: {$datasourceName}");
            return null;
        } catch (\Exception $e) {
            $this->logger->error("Error finding datasource key: " . $e->getMessage());
            return null;
        }
    }
}