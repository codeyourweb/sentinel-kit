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

final class MainController extends AbstractController
{
    private $httpClient;

    function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    #[Route('/', name: 'app_main')]
    public function index(): Response
    {
        return $this->render('main/index.html.twig', [
            'controller_name' => 'MainController',
        ]);
    }


    #[Route('/api/health-check/{service}', name: 'app_service_health_check', methods: ['GET', 'HEAD'])]
    public function serviceHealthCheck(string $service): JsonResponse
    {
        $services = [
            'kibana' => 'http://sentinel-kit-utils-kibana:5601',
            'grafana' => 'http://sentinel-kit-utils-grafana:3000',
            'backend' => 'http://sentinel-kit-app-backend:8000'
        ];

        if (!isset($services[$service])) {
            return new JsonResponse([
                'service' => $service,
                'status' => 'error',
                'error' => 'Unknown service'
            ], Response::HTTP_BAD_REQUEST);
        }

        $url = $services[$service];
        
        try {
            $response = $this->httpClient->request('GET', $url, [
                'timeout' => 10,
                'verify_peer' => false,
                'verify_host' => false,
                'max_redirects' => 0
            ]);
            $statusCode = $response->getStatusCode();
            
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
