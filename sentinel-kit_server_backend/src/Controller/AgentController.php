<?php

namespace App\Controller;

use App\Entity\Agent;
use App\Repository\ServerTokenRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Agent Controller handles agent registration and management endpoints.
 * 
 * This controller manages the lifecycle of monitoring agents in the Sentinel Kit system,
 * including agent registration with server token validation and periodic beacon calls.
 */
class AgentController extends AbstractController
{
    /**
     * Register a new monitoring agent in the system.
     * 
     * This endpoint allows new agents to register themselves with the Sentinel Kit backend.
     * It validates the provided server token and creates a new agent record if valid.
     * 
     * @param Request $request HTTP request containing agent registration data
     * @param ServerTokenRepository $serverTokenRepository Repository for validating server tokens
     * @param \Symfony\Component\Validator\Validator\ValidatorInterface $validator Entity validator
     * @param EntityManagerInterface $em Entity manager for database operations
     * 
     * @return Response JSON response containing either client token (success) or error message
     * 
     * Expected request body:
     * {
     *   "hostname": "string",     // Agent hostname (required)
     *   "osName": "string",       // Operating system name (required)  
     *   "osVersion": "string",    // Operating system version (required)
     *   "serverToken": "string"   // Valid server token (required)
     * }
     * 
     * Success response (200):
     * {
     *   "clientToken": "string"   // Generated client token for the agent
     * }
     * 
     * Error responses:
     * - 401: Invalid server token
     * - 400: Missing required fields or invalid data
     * - 500: Database error during agent creation
     */
    #[Route('/agent/agent-registration', name: 'app_register_agent', methods: ['POST'])]
    public function registerAgent(Request $request, ServerTokenRepository $serverTokenRepository, \Symfony\Component\Validator\Validator\ValidatorInterface $validator, EntityManagerInterface $em): Response
    {
        // Extract and validate input data from request body
        $inputData = json_decode($request->getContent(), true);
        $data = [];
        $data['hostname'] = $inputData['hostname'] ?? null;
        $data['osName'] = $inputData['osName'] ?? null;
        $data['osVersion'] = $inputData['osVersion'] ?? null;
        $data['serverToken'] = $inputData['serverToken'] ?? null;

        // Validate server token before proceeding
        if ($serverTokenRepository->isServerTokenValid($data['serverToken']) === false) {
            return new JsonResponse(['error' => 'You must provide a valid server token.'], Response::HTTP_UNAUTHORIZED);
        }

        // Ensure all required fields are provided
        if($data['hostname'] === null || $data['osName'] === null || $data['osVersion'] === null) {
            return new JsonResponse(['error' => 'Agent registration must include hostname, osName and osVersion.'], Response::HTTP_BAD_REQUEST);
        }

        // Create new agent entity with provided data
        $agent = new Agent();
        $agent->setHostname($data['hostname']);
        $agent->setOsName($data['osName']);
        $agent->setOsVersion($data['osVersion']);
        
        // Validate agent entity before persisting
        $errors = $validator->validate($agent);
 
        if (count($errors) > 0) {
            return new JsonResponse(['error' => 'Invalid agent data. '.addslashes((string) $errors)], Response::HTTP_BAD_REQUEST);
        }
        
        // Persist agent to database
        try {
            $em->persist($agent);
            $em->flush();
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to register agent. '.addslashes($e->getMessage())], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Return client token for authenticated agent operations
        return new JsonResponse(['clientToken' => $agent->getToken()], Response::HTTP_OK);
    }

    /**
     * Handle agent beacon/heartbeat requests.
     * 
     * This endpoint receives periodic heartbeat signals from registered agents
     * to indicate they are alive and operational. Currently returns a simple
     * acknowledgment but can be extended to update agent status or collect metrics.
     * 
     * @param Request $request HTTP request from the agent
     * 
     * @return Response JSON response indicating successful beacon receipt
     * 
     * Success response (200):
     * {
     *   "status": "ok"
     * }
     */
    #[Route('/agent/agent-beacon', name: 'app_agent_beacon', methods: ['POST'])]
    public function agentBeacon(Request $request) : Response
    {
        // TODO: Add agent authentication and status tracking
        // Currently just acknowledges the beacon signal
        $response = new Response();
        $response->setContent(json_encode(['status' => 'ok']), Response::HTTP_OK, ['Content-Type' => 'application/json']);
        return $response;
    }
}