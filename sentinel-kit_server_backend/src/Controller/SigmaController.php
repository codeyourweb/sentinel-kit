<?php

namespace App\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\SigmaRule;
use App\Entity\SigmaRuleVersion;
use App\Service\SigmaRuleValidator;
use App\Service\ElastalertRuleValidator;
class SigmaController extends AbstractController{

    private $entityManger;
    private $serializer;
    private $sigmaValidator;
    private $elastalertValidator;

    public function __construct(EntityManagerInterface $em, SerializerInterface $serializer, SigmaRuleValidator $sigmaValidator, ElastalertRuleValidator $elastalertValidator){
        $this->entityManger = $em;
        $this->serializer = $serializer;
        $this->sigmaValidator = $sigmaValidator;
        $this->elastalertValidator = $elastalertValidator;
    }



    #[Route('/api/rules/sigma/add_rule', name: 'app_sigma_add_rule', methods: ['POST'])]
    public function SaveNewSigmaRule(Request $request): JsonResponse {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['rule_content'])) {
            return new JsonResponse(['error' => 'Missing rule content'], Response::HTTP_BAD_REQUEST);
        }

        $ruleContent = $data['rule_content'];
        $validationResult = $this->sigmaValidator->validateSigmaRuleContent($ruleContent);

        if (isset($validationResult['error'])) {
            return new JsonResponse(['error' => $validationResult['error']], Response::HTTP_BAD_REQUEST);
        }

        if (!empty($validationResult['missingFields'])) {
            $missingFieldsString = implode(", ", $validationResult['missingFields']);
            return new JsonResponse(['error' => "Missing required fields: " . $missingFieldsString], Response::HTTP_BAD_REQUEST);
        }

        $yamlData = $validationResult['yamlData'];
        
        $existingRule = $this->entityManger->getRepository(SigmaRule::class)->findOneBy(['title' => $yamlData['title']]);
        if ($existingRule) {
            return new JsonResponse(['error' => 'A rule with this title already exists'], Response::HTTP_BAD_REQUEST);
        }

        $newRule = new SigmaRule();
        $newRuleVersion = new SigmaRuleVersion();
        $newRule->setTitle($yamlData['title']);
        $newRule->setDescription($yamlData['description']);
        $newRule->setActive(false);
        $newRuleVersion->setContent(Yaml::dump($yamlData, 4));
        $newRuleVersion->setLevel($yamlData['level']);
        $newRule->addVersion($newRuleVersion);

        $existingVersion = $this->entityManger->getRepository(SigmaRuleVersion::class)->findOneBy(['hash' => $newRuleVersion->getHash()]);
        if ($existingVersion) {
            return new JsonResponse(['error' => 'A rule with the exact same content already exists'], Response::HTTP_BAD_REQUEST);
        }

        try{
            $this->entityManger->persist($newRule);
            $this->entityManger->flush();
        } catch (\Exception $e) {
            return  new JsonResponse(['error' => 'Failed to save the rule: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['error' => '', 'rule_id' => $newRule->getId()], Response::HTTP_OK);
    }


    #[Route('/api/rules/sigma/list', name: 'app_sigma_list', methods: ['GET'])]
    public function listRulesSummary(Request $request): Response
    {
        $rules = $this->entityManger->getRepository(SigmaRule::class)->summaryFindAll();
        return new JsonResponse($rules, Response::HTTP_OK);
    }

    #[Route('/api/rules/sigma/{ruleId}/status', name:'app_sigma_change_rule_status', methods: ['PUT'])]
    public function editRuleStatus(Request $request, int $ruleId): JsonResponse{
        $rule = $this->entityManger->getRepository(SigmaRule::class)->find($ruleId);
        if(!$rule){
            return new JsonResponse(['error' => 'Rule not found'], Response::HTTP_NOT_FOUND);
        }
        $data = json_decode($request->getContent(), true);
        if (!isset($data['active'])) {
            return new JsonResponse(['error' => 'Missing active status'], Response::HTTP_BAD_REQUEST);
        }
        $rule->setActive($data['active']);

        if(!$data['active']){
            $this->elastalertValidator->removeElastalertRule($rule->getRuleLatestVersion());
        }else{
            $e = $this->elastalertValidator->createElastalertRule($rule->getRuleLatestVersion());
            if (isset($e['error'])) {
                return new JsonResponse(['error' => $e['error']], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        $this->entityManger->flush();
        return new JsonResponse(['message' => 'Rule status updated successfully'], Response::HTTP_OK);
    }

    #[Route('api/rules/sigma/{ruleId}/details', name:'app_sigma_get_rule', methods: ['GET'])]
    public function getRule(Request $request, int $ruleId): JsonResponse {
        $rule = $this->entityManger->getRepository(SigmaRule::class)->find($ruleId);
        if (!$rule) {
            return new JsonResponse(['error' => 'Rule not found'], Response::HTTP_NOT_FOUND);
        }
        
        $serializedRule = $this->serializer->serialize($rule, 'json', ['groups' => ['rule_details']]);
        return new JsonResponse($serializedRule, Response::HTTP_OK, [], true);
    }

    #[Route('/api/rules/sigma/{ruleId}/add_version', name: 'app_sigma_add_version', methods: ['POST'])]
    public function addRuleVersion(Request $request, int $ruleId): JsonResponse {
        $rule = $this->entityManger->getRepository(SigmaRule::class)->find($ruleId);
        if (!$rule) {
            return new JsonResponse(['error' => 'Rule not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!isset($data['rule_content'])) {
            return new JsonResponse(['error' => 'Missing rule content'], Response::HTTP_BAD_REQUEST);
        }

        $ruleContent = $data['rule_content'];
        $validationResult = $this->sigmaValidator->validateSigmaRuleContent($ruleContent);

        if (isset($validationResult['error'])) {
            return new JsonResponse(['error' => $validationResult['error']], Response::HTTP_BAD_REQUEST);
        }

        if (!empty($validationResult['missingFields'])) {
            $missingFieldsString = implode(", ", $validationResult['missingFields']);
            return new JsonResponse(['error' => "Missing required fields: " . $missingFieldsString], Response::HTTP_BAD_REQUEST);
        }

        $yamlData = $validationResult['yamlData'];

        $newRuleVersion = new SigmaRuleVersion();
        $newRuleVersion->setContent($ruleContent);
        $newRuleVersion->setLevel($yamlData['level']);
        $newRuleVersion->setRule($rule);

        $existingVersion = $this->entityManger->getRepository(SigmaRuleVersion::class)->findOneBy(['hash' => $newRuleVersion->getHash()]);
        if ($existingVersion) {
            return new JsonResponse(['error' => 'A version with the exact same content already exists'], Response::HTTP_BAD_REQUEST);
        }

        if ($rule->getTitle() !== $yamlData['title']) {
            $rule->setTitle($yamlData['title']);
        }
        if ($rule->getDescription() !== $yamlData['description']) {
            $rule->setDescription($yamlData['description']);
        }

        $existingRulesWithTitle = $this->entityManger->getRepository(SigmaRule::class)->findBy(['title' => $yamlData['title']]);
        foreach ($existingRulesWithTitle as $existingRule) {
            if ($existingRule->getId() !== $rule->getId()) {
                return new JsonResponse(['error' => 'Another rule with this title already exists'], Response::HTTP_BAD_REQUEST);
            }
        }

        $latestVersion = $this->entityManger->getRepository(SigmaRuleVersion::class)->findOneBy(
            ['rule' => $rule],
            ['id' => 'DESC']
        );
        if ($latestVersion && $latestVersion->getHash() === $newRuleVersion->getHash()) {
            return new JsonResponse(['error' => 'The new version content is identical to the latest version'], Response::HTTP_BAD_REQUEST);
        }


        try {
            $this->entityManger->persist($newRuleVersion);
            $this->entityManger->flush();
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to save the new version: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['error' => '', 'version_id' => $newRuleVersion->getId()], Response::HTTP_OK);
    }

    #[Route('api/rules/sigma/{ruleId}', name:'app_sigma_delete_rule', methods: ['DELETE'])]
    public function deleteRule(Request $request, int $ruleId): JsonResponse {
        $rule = $this->entityManger->getRepository(SigmaRule::class)->find($ruleId);
        if (!$rule) {
            return new JsonResponse(['error' => 'Rule not found'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManger->remove($rule);
        $this->entityManger->flush();

        return new JsonResponse(['message' => 'Rule deleted successfully'], Response::HTTP_OK);
    }
}