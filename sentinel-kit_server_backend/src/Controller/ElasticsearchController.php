<?php

namespace App\Controller;

use App\Service\ElasticsearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/elasticsearch')]
class ElasticsearchController extends AbstractController
{
    private ElasticsearchService $elasticsearchService;

    public function __construct(ElasticsearchService $elasticsearchService)
    {
        $this->elasticsearchService = $elasticsearchService;
    }

    #[Route('/search', name: 'elasticsearch_search', methods: ['POST'])]
    public function search(Request $request): JsonResponse
    {
        try {
            $requestData = json_decode($request->getContent(), true);

            if (!$requestData) {
                return $this->json([
                    'error' => 'Invalid JSON in request body'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Log the incoming query for debugging
            error_log('Elasticsearch query received: ' . json_encode($requestData));

            if (!$this->isAllowedQuery($requestData)) {
                error_log('Query rejected by validation: ' . json_encode($requestData));
                return $this->json([
                    'error' => 'Only read-only search operations are allowed'
                ], Response::HTTP_FORBIDDEN);
            }

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
     * Validate that the query only contains allowed read-only operations
     */
    private function isAllowedQuery(array $query): bool
    {
        // Simplified validation - just check for obviously dangerous operations
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

        foreach ($forbiddenOperations as $forbidden) {
            if (strpos($queryLower, $forbidden) !== false) {
                error_log("Forbidden operation detected: $forbidden in query: $queryString");
                return false;
            }
        }

        return true;
    }
}