<?php

namespace App\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\SigmaRule;

class SigmaController extends AbstractController{

    private $entityManger;
    private $serializer;

    public function __construct(EntityManagerInterface $em, SerializerInterface $serializer){
        $this->entityManger = $em;
        $this->serializer = $serializer;
    }


    #[Route('/api/rules/sigma/list', name: 'app_sigma_list', methods: ['GET'])]
    public function listRulesSummary(Request $request): Response
    {
        $rules = $this->entityManger->getRepository(SigmaRule::class)->summaryFindAll();
        return new JsonResponse($rules, Response::HTTP_OK);
    }

    #[Route('/api/rules/sigma/{ruleId}/status', name:'app_sigma_change_rule_status', methods: ['PUT'])]
    public function editRuleStatus(Request $request, int $ruleId): Response{
        $rule = $this->entityManger->getRepository(SigmaRule::class)->find($ruleId);
        if(!$rule){
            return new JsonResponse(['error' => 'Rule not found'], Response::HTTP_NOT_FOUND);
        }
        $data = json_decode($request->getContent(), true);
        if (!isset($data['active'])) {
            return new JsonResponse(['error' => 'Missing active status'], Response::HTTP_BAD_REQUEST);
        }
        $rule->setActive($data['active']);
        $this->entityManger->flush();
        return new JsonResponse(['message' => 'Rule status changed successfully'], Response::HTTP_OK);
    }

    #[Route('api/rules/sigma/{ruleId}/details', name:'app_sigma_get_rule', methods: ['GET'])]
    public function getRule(Request $request, int $ruleId): Response {
        $rule = $this->entityManger->getRepository(SigmaRule::class)->find($ruleId);
        if (!$rule) {
            return new JsonResponse(['error' => 'Rule not found'], Response::HTTP_NOT_FOUND);
        }
        
        $serializedRule = $this->serializer->serialize($rule, 'json', ['groups' => ['rule_details']]);
        return new JsonResponse($serializedRule, Response::HTTP_OK, [], true);
    }
}