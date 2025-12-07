<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Repository\ServerTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Agent;

/**
 * Main Controller handles core application routes and health checks.
 * 
 * This controller provides the main application entry point and service health
 * monitoring capabilities. It includes endpoints for the home page and checking
 * the status of various services in the Sentinel Kit infrastructure.
 */
final class MainController extends AbstractController
{
    /**
     * HTTP client for making service health check requests.
     */
    private $httpClient;

    /**
     * Controller constructor with HTTP client dependency injection.
     * 
     * @param HttpClientInterface $httpClient HTTP client for external service calls
     */
    function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Render the main application home page.
     * 
     * Displays the primary interface of the Sentinel Kit application.
     * 
     * @return Response Rendered Twig template for the main page
     */
    #[Route('/', name: 'app_main')]
    public function index(): Response
    {
        return $this->render('main/index.html.twig', [
            'controller_name' => 'MainController',
        ]);
    }


    /**
     * Check the health status of a specific service.
     * 
     * Performs HTTP health checks on critical services in the Sentinel Kit
     * infrastructure including Kibana, Grafana, and the backend API.
     * Returns detailed status information for monitoring and alerting.
     * 
     * @param string $service Service name to check (kibana, grafana, backend)
     * 
     * @return JsonResponse Service health status and metadata
     * 
     * Available services:
     * - kibana: Elasticsearch Kibana dashboard (port 5601)
     * - grafana: Grafana monitoring dashboard (port 3000)  
     * - backend: Sentinel Kit backend API (port 8000)
     * 
     * Success response (200):
     * {
     *   "service": string,
     *   "url": string,
     *   "status": "healthy|unhealthy",
     *   "error": string|null,
     *   "httpStatus": number|null,
     *   "lastChecked": string (ISO timestamp)
     * }
     * 
     * Error response (400): {"service": string, "status": "error", "error": "Unknown service"}
     */
    #[Route('/api/health-check/{service}', name: 'app_service_health_check', methods: ['GET', 'HEAD'])]
    public function serviceHealthCheck(string $service): JsonResponse
    {
        // Define available services and their health check URLs
        $services = [
            'kibana' => 'http://sentinel-kit-utils-kibana:5601',
            'grafana' => 'http://sentinel-kit-utils-grafana:3000',
            'backend' => 'http://sentinel-kit-app-backend:8000'
        ];

        // Validate service parameter
        if (!isset($services[$service])) {
            return new JsonResponse([
                'service' => $service,
                'status' => 'error',
                'error' => 'Unknown service'
            ], Response::HTTP_BAD_REQUEST);
        }

        $url = $services[$service];
        
        try {
            // Perform health check request with timeout and SSL settings
            $response = $this->httpClient->request('GET', $url, [
                'timeout' => 10,
                'verify_peer' => false,
                'verify_host' => false,
                'max_redirects' => 0
            ]);
            $statusCode = $response->getStatusCode();
            
            // Determine health status based on HTTP response
            if ($statusCode >= 200 && $statusCode < 400) {
                $status = 'healthy';
                $error = null;
            } else {
                $status = 'unhealthy';
                $error = $this->getErrorMessage($statusCode);
            }
            
            return new JsonResponse([
                'service' => $service,
                'url' => $url,
                'status' => $status,
                'error' => $error,
                'httpStatus' => $statusCode,
                'lastChecked' => (new \DateTime())->format('c')
            ], Response::HTTP_OK);
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'service' => $service,
                'url' => $url,
                'status' => 'unhealthy',
                'error' => 'Connection failed: ' . $e->getMessage(),
                'httpStatus' => null,
                'lastChecked' => (new \DateTime())->format('c')
            ], Response::HTTP_OK);
        }
    }

    /**
     * Convert HTTP status codes to human-readable error messages.
     * 
     * Provides descriptive error messages for common HTTP status codes
     * encountered during service health checks.
     * 
     * @param int $statusCode HTTP status code to interpret
     * 
     * @return string Human-readable error description
     */
    private function getErrorMessage(int $statusCode): string
    {
        return match($statusCode) {
            502 => 'Bad Gateway: Service is down or unreachable',
            503 => 'Service Unavailable: Service is temporarily down',
            504 => 'Gateway Timeout: Service is not responding',
            500 => 'Internal Server Error: Service has internal issues',
            404 => 'Not Found: Service endpoint not found',
            403 => 'Forbidden: Access denied',
            401 => 'Unauthorized: Authentication required',
            302, 301 => 'Redirect: Service is redirecting requests',
            default => "HTTP $statusCode: Service returned error status"
        };
    }
}
