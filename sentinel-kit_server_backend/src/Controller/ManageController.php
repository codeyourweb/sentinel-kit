<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Agent;

/**
 * Management Controller handles administrative and management endpoints.
 * 
 * This controller provides administrative functionality for the Sentinel Kit
 * system. Currently implements basic authentication status checking and
 * can be extended with additional management features like system status,
 * configuration management, and administrative operations.
 */
final class ManageController extends AbstractController
{
    /**
     * Entity manager for database operations.
     */
    private $entityManager;
    
    /**
     * Controller constructor with dependency injection.
     * 
     * @param EntityManagerInterface $entityManager Doctrine entity manager for data access
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }


    /**
     * Management authentication and status endpoint.
     * 
     * Provides a simple endpoint to verify management API accessibility
     * and authentication status. Can be used for health checks and
     * administrative interface connectivity verification.
     * 
     * @param Request $request HTTP request
     * 
     * @return JsonResponse Status confirmation
     * 
     * Success response (200):
     * {
     *   "status": "ok"
     * }
     */
    #[Route('/api/manage', name:'app_manage_main', methods: ['GET'])]
    public function auth(Request $request): JsonResponse
    {
        // Return simple status confirmation for management API
        return new JsonResponse(['status' => 'ok'], Response::HTTP_OK);
    }
}
