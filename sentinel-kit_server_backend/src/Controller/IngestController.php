<?php

namespace App\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Datasource;

/**
 * Ingest Controller handles log data ingestion into the Sentinel Kit system.
 * 
 * This controller provides endpoints for external systems and agents to submit
 * log data for processing and storage. It validates ingest keys, manages
 * data source routing, and forwards data to the Fluentbit processing pipeline.
 * Supports JSON-formatted log data with authentication and size validation.
 */
class IngestController extends AbstractController
{

    /**
     * Entity manager for database operations.
     */
    private $entityManager;
    
    /**
     * HTTP client for forwarding data to Fluentbit.
     */
    private $httpClient;
    
    /**
     * Controller constructor with dependency injection.
     * 
     * @param EntityManagerInterface $em Doctrine entity manager
     * @param HttpClientInterface $httpClient HTTP client for external service calls
     */
    public function __construct(EntityManagerInterface $em, HttpClientInterface $httpClient)
    {
        $this->entityManager = $em;
        $this->httpClient = $httpClient;
    }


    /**
     * Ingest JSON-formatted log data into the Sentinel Kit system.
     * 
     * Accepts log data in JSON format, validates the ingest key and data source
     * configuration, and forwards the data to the Fluentbit server for processing
     * and storage in Elasticsearch. Supports both single log entries and arrays
     * of log entries.
     * 
     * @param Request $request HTTP request containing log data and authentication
     * 
     * @return Response Processing result or error message
     * 
     * Required headers:
     * - X-Ingest-Key: Valid data source ingest key
     * 
     * Request body: JSON log data (single object or array of objects)
     * 
     * Success response: Forwards Fluentbit server response
     * 
     * Error responses:
     * - 401: Invalid, expired, or not-yet-valid ingest key
     * - 400: Request too large, invalid JSON, or malformed data
     * - 500: Fluentbit server not configured
     * 
     * Size limit: 128MB maximum request body size
     */
    #[Route('/ingest/json', name: 'app_ingest_json', methods: ['POST'])]
    public function ingestData(Request $request): Response
    {
        // Extract and validate ingest key from request headers
        $ingestKey = $request->headers->get('X-Ingest-Key');
        $datasource = $this->entityManager->getRepository(Datasource::class)->findOneBy(['ingestKey' => $ingestKey]);
        if(null === $datasource) {
            return new JsonResponse(['error' => 'Invalid Ingest Key'], Response::HTTP_UNAUTHORIZED);
        }

        // Check if ingest key is within valid time range
        if($datasource->getValidFrom() !== null && $datasource->getValidFrom() > new \DateTime()) {
            return new JsonResponse(['error' => 'Ingest Key not yet valid'], Response::HTTP_UNAUTHORIZED);
        }

        if ($datasource->getValidTo() !== null && $datasource->getValidTo() > new \DateTime()) {
            return new JsonResponse(['error' => 'Ingest Key expired'], Response::HTTP_UNAUTHORIZED);
        }

        // Enforce request size limit (128MB)
        if (strlen($request->getContent()) > 128 * 1024 * 1024) {
            return new JsonResponse(['error' => 'Request body too large'], Response::HTTP_BAD_REQUEST);
        }

        // Parse and validate JSON input data
        $inputData = json_decode($request->getContent(), true);
        
        if (json_last_error() !== JSON_ERROR_NONE || (!is_array($inputData) && !is_object($inputData))) {
            return new JsonResponse(['error' => 'Invalid JSON data'], Response::HTTP_BAD_REQUEST);
        } 

        // Ensure data is in array format (wrap single objects)
        if(!isset($inputData[0]) || !is_array($inputData[0])) {
            $inputData = [$inputData];
        }

        // Add target index information to each log entry
        foreach ($inputData as $k=>$v) {
            $inputData[$k]['target_index'] = "sentinelkit-" . $datasource->getTargetIndex();
        }

        // Verify Fluentbit server is configured
        if ($_ENV['FLUENTBIT_SERVER_URL'] === null || $_ENV['FLUENTBIT_SERVER_URL'] === '') {
            return new JsonResponse(['error' => 'Fluentbit server endpoint not configured'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Forward processed data to Fluentbit server for ingestion
        $response = $this->httpClient->request('POST', $_ENV['FLUENTBIT_SERVER_URL'], ['json' => $inputData]);
        return new Response($response->getContent(), $response->getStatusCode());
    }
}