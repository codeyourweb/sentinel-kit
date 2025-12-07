<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ElasticsearchService;
use Psr\Log\LoggerInterface;

/**
 * Datasources Controller manages log data sources and their metrics.
 * 
 * This controller provides endpoints for managing and monitoring various
 * log data sources integrated with Elasticsearch. It handles data source
 * discovery, statistics retrieval, and event querying across different
 * log sources in the Sentinel Kit system.
 */
#[Route('/api/datasources', name: 'api_datasources_')]
class DatasourcesController extends AbstractController
{
    /**
     * Elasticsearch service for data source operations.
     */
    private ElasticsearchService $elasticsearchService;
    
    /**
     * Logger for debugging and error tracking.
     */
    private LoggerInterface $logger;

    /**
     * Controller constructor with dependency injection.
     * 
     * @param ElasticsearchService $elasticsearchService Service for Elasticsearch operations
     * @param LoggerInterface $logger Logger for debugging and error tracking
     */
    public function __construct(ElasticsearchService $elasticsearchService, LoggerInterface $logger)
    {
        $this->elasticsearchService = $elasticsearchService;
        $this->logger = $logger;
    }

    /**
     * Get all available data sources with their statistics.
     * 
     * Retrieves information about all log data sources by querying Elasticsearch
     * indices. Groups indices by data source type and aggregates statistics
     * including document counts, storage size, and health status.
     * 
     * @param Request $request HTTP request
     * 
     * @return JsonResponse Array of data source information
     * 
     * Response format:
     * [
     *   {
     *     "name": string (display name),
     *     "key": string (API key),
     *     "indices": [{"name": string, "documents": number, "sizeBytes": number, "health": string}],
     *     "totalDocuments": number,
     *     "totalSizeBytes": number,
     *     "status": "active|warning|error"
     *   }
     * ]
     */
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

    /**
     * Get ingestion statistics for a specific data source.
     * 
     * Retrieves time-series data about log ingestion rates, volumes,
     * and trends for a specific data source over a specified time range.
     * 
     * @param Request $request HTTP request with query parameters
     * @param string $datasource Name of the data source
     * 
     * @return JsonResponse Ingestion statistics or error message
     * 
     * Query parameters:
     * - timeRange: Time period for statistics (24h, 7d, 30d) - default: 7d
     * 
     * Success response (200):
     * {
     *   "timeRange": string,
     *   "datasource": string,
     *   "data": array (time-series ingestion data)
     * }
     * 
     * Error responses:
     * - 404: Data source not found
     * - 500: Elasticsearch query failure
     */
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

    /**
     * Get events from a specific data source with pagination and filtering.
     * 
     * Retrieves log events from a specific data source with support for
     * pagination, date range filtering, and result limiting.
     * 
     * @param Request $request HTTP request with query parameters
     * @param string $datasource Name of the data source
     * 
     * @return JsonResponse Paginated events or error message
     * 
     * Query parameters:
     * - page: Page number (default: 1)
     * - pageSize: Events per page (default: 50)
     * - startDate: Start date for filtering (ISO format)
     * - endDate: End date for filtering (ISO format)
     * 
     * Success response (200):
     * {
     *   "events": array,
     *   "total": number,
     *   "page": number,
     *   "pageSize": number,
     *   "totalPages": number
     * }
     * 
     * Error responses:
     * - 404: Data source not found
     * - 500: Elasticsearch query failure
     */
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

    /**
     * Extract human-readable datasource name from Elasticsearch index name.
     * 
     * Parses various index naming patterns to extract a clean, formatted
     * data source name for display purposes.
     * 
     * @param string $indexName Full Elasticsearch index name
     * 
     * @return string Formatted data source display name
     */
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

    /**
     * Extract datasource key from Elasticsearch index name for API operations.
     * 
     * Parses index names to extract the raw data source key without formatting,
     * used for internal API operations and Elasticsearch queries.
     * 
     * @param string $indexName Full Elasticsearch index name
     * 
     * @return string Raw data source key for API use
     */
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

    /**
     * Find the internal datasource key by display name.
     * 
     * Searches through all available Elasticsearch indices to find the
     * internal key corresponding to a given display name.
     * 
     * @param string $datasourceName Display name of the data source
     * 
     * @return string|null Internal datasource key or null if not found
     */
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