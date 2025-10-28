<?php

namespace App\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

class AgentController extends AbstractController{
    #[Route('/agent/agent-registration', name: 'app_register_agent', methods: ['POST'])]
    public function registerAgent(Request $request, ServerTokenRepository $serverTokenRepository, \Symfony\Component\Validator\Validator\ValidatorInterface $validator, EntityManagerInterface $em): Response
    {
        $inputData = json_decode($request->getContent(), true);
        $data = [];
        $data['hostname'] = $inputData['hostname'] ?? null;
        $data['osName'] = $inputData['osName'] ?? null;
        $data['osVersion'] = $inputData['osVersion'] ?? null;
        $data['serverToken'] = $inputData['serverToken'] ?? null;

        if ($serverTokenRepository->isServerTokenValid($data['serverToken']) === false) {
            return new JsonResponse(['error' => 'You must provide a valid server token.'], Response::HTTP_UNAUTHORIZED);
        }

        if($data['hostname'] === null || $data['osName'] === null || $data['osVersion'] === null) {
            return new JsonResponse(['error' => 'Agent registration must include hostname, osName and osVersion.'], Response::HTTP_BAD_REQUEST);
        }

        $agent = new Agent();
        $agent->setHostname($data['hostname']);
        $agent->setOsName($data['osName']);
        $agent->setOsVersion($data['osVersion']);
        $errors = $validator->validate($agent);
 
        if (count($errors) > 0) {
            return new JsonResponse(['error' => 'Invalid agent data. '.addslashes((string) $errors)], Response::HTTP_BAD_REQUEST);
        }
        
        try {
            $em->persist($agent);
            $em->flush();
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to register agent. '.addslashes($e->getMessage())], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['clientToken' => $agent->getToken()], Response::HTTP_OK);
    }

    #[Route('/agent/agent-beacon', name: 'app_agent_beacon', methods: ['POST'])]
    public function agentBeacon(Request $request) : Response
    {
        
        $response = new Response();
        $response->setContent(json_encode(['status' => 'ok']), Response::HTTP_OK, ['Content-Type' => 'application/json']);
        return $response;
    }
}