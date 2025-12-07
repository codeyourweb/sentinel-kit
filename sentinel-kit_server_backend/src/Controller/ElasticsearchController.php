<?php

namespace App\Controller;

use App\Service\ElasticsearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Elasticsearch Controller provides direct Elasticsearch query capabilities.
 * 
 * This controller acts as a secure proxy for Elasticsearch operations,
 * allowing controlled access to raw Elasticsearch queries while preventing
 * dangerous write operations. It's primarily used for advanced search
 * and analytics operations on log data.
 */
#[Route('/api/elasticsearch')]
class ElasticsearchController extends AbstractController
{
    /**
     * Elasticsearch service for query execution.
     */
    private ElasticsearchService $elasticsearchService;

    /**
     * Controller constructor with dependency injection.
     * 
     * @param ElasticsearchService $elasticsearchService Service for Elasticsearch operations
     */
    public function __construct(ElasticsearchService $elasticsearchService)
    {
        $this->elasticsearchService = $elasticsearchService;
    }

    /**
     * Execute a raw Elasticsearch search query.
     * 
     * Accepts and executes raw Elasticsearch queries with security validation
     * to prevent destructive operations. Only read-only search operations
     * are permitted through this endpoint.
     * 
     * @param Request $request HTTP request containing Elasticsearch query
     * 
     * @return JsonResponse Search results or error message
     * 
     * Request body: Valid Elasticsearch query JSON
     * 
     * Success response (200):
     * {
     *   "success": true,
     *   "data": object (Elasticsearch response)
     * }
     * 
     * Error responses:
     * - 400: Invalid JSON in request body
     * - 403: Forbidden operation (write/delete operations)
     * - 500: Elasticsearch query execution error
     */
    #[Route('/search', name: 'elasticsearch_search', methods: ['POST'])]
    public function search(Request $request): JsonResponse
    {
        try {
            // Parse JSON request body
            $requestData = json_decode($request->getContent(), true);

            if (!$requestData) {
                return $this->json([
                    'error' => 'Invalid JSON in request body'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Log incoming query for debugging and audit purposes
            error_log('Elasticsearch query received: ' . json_encode($requestData));

            // Validate query for security (prevent write/delete operations)
            if (!$this->isAllowedQuery($requestData)) {
                error_log('Query rejected by validation: ' . json_encode($requestData));
                return $this->json([
                    'error' => 'Only read-only search operations are allowed'
                ], Response::HTTP_FORBIDDEN);
            }

            // Execute the validated query through Elasticsearch service
            $result = $this->elasticsearchService->rawQuery($requestData);

            return $this->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            error_log('Elasticsearch controller error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            return $this->json([
                'error' => 'Elasticsearch query error: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Validate that the query only contains allowed read-only operations.
     * 
     * Security validation method that checks incoming Elasticsearch queries
     * for potentially dangerous write or delete operations. Prevents
     * unauthorized data modification through the search endpoint.
     * 
     * @param array $query Elasticsearch query array to validate
     * 
     * @return bool True if query is safe (read-only), false if contains forbidden operations
     */
    private function isAllowedQuery(array $query): bool
    {
        // Define list of forbidden operations that could modify or delete data
        $forbiddenOperations = [
            'delete',
            'update', 
            'bulk',
            '_delete',
            '_update',
            '_bulk'
        ];

        // Convert query to string for simple pattern matching
        $queryString = json_encode($query);
        $queryLower = strtolower($queryString);

        // Check for any forbidden operations in the query
        foreach ($forbiddenOperations as $forbidden) {
            if (strpos($queryLower, $forbidden) !== false) {
                error_log("Forbidden operation detected: $forbidden in query: $queryString");
                return false;
            }
        }

        return true;
    }
}