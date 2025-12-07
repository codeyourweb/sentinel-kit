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
/**
 * Sigma Rule Controller manages Sigma detection rules and their versions.
 * 
 * This controller handles CRUD operations for Sigma rules, which are used for
 * security event detection and alerting. It manages rule creation, validation,
 * versioning, activation/deactivation, and integration with Elastalert for
 * automated alert generation.
 * 
 * Sigma rules follow the Sigma standard format for describing log events
 * and are converted to Elastalert rules for deployment.
 */
class SigmaController extends AbstractController
{

    /**
     * Entity manager for database operations.
     */
    private $entityManger;
    
    /**
     * Serializer for data transformation.
     */
    private $serializer;
    
    /**
     * Sigma rule validator service.
     */
    private $sigmaValidator;
    
    /**
     * Elastalert rule validator and converter service.
     */
    private $elastalertValidator;

    /**
     * Controller constructor with dependency injection.
     * 
     * @param EntityManagerInterface $em Doctrine entity manager
     * @param SerializerInterface $serializer Symfony serializer
     * @param SigmaRuleValidator $sigmaValidator Sigma rule validation service
     * @param ElastalertRuleValidator $elastalertValidator Elastalert rule management service
     */
    public function __construct(EntityManagerInterface $em, SerializerInterface $serializer, SigmaRuleValidator $sigmaValidator, ElastalertRuleValidator $elastalertValidator)
    {
        $this->entityManger = $em;
        $this->serializer = $serializer;
        $this->sigmaValidator = $sigmaValidator;
        $this->elastalertValidator = $elastalertValidator;
    }



    /**
     * Create a new Sigma rule with initial version.
     * 
     * Validates and saves a new Sigma rule in YAML format. Performs comprehensive
     * validation including syntax checking, required field validation, and
     * duplicate detection. Creates both the rule entity and its first version.
     * 
     * @param Request $request HTTP request containing rule content
     * 
     * @return JsonResponse Rule creation result or validation errors
     * 
     * Request body:
     * {
     *   "rule_content": string  // YAML content of the Sigma rule (required)
     * }
     * 
     * Success response (200):
     * {
     *   "error": "",
     *   "rule_id": number  // ID of the created rule
     * }
     * 
     * Error responses:
     * - 400: Missing content, validation errors, duplicate title/content
     * - 500: Database save failure
     */
    #[Route('/api/rules/sigma/add_rule', name: 'app_sigma_add_rule', methods: ['POST'])]
    public function SaveNewSigmaRule(Request $request): JsonResponse
    {
        // Extract rule content from request
        $data = json_decode($request->getContent(), true);
        if (!isset($data['rule_content'])) {
            return new JsonResponse(['error' => 'Missing rule content'], Response::HTTP_BAD_REQUEST);
        }

        $ruleContent = $data['rule_content'];
        
        // Validate Sigma rule syntax and structure
        $validationResult = $this->sigmaValidator->validateSigmaRuleContent($ruleContent);

        if (isset($validationResult['error'])) {
            return new JsonResponse(['error' => $validationResult['error']], Response::HTTP_BAD_REQUEST);
        }

        if (!empty($validationResult['missingFields'])) {
            $missingFieldsString = implode(", ", $validationResult['missingFields']);
            return new JsonResponse(['error' => "Missing required fields: " . $missingFieldsString], Response::HTTP_BAD_REQUEST);
        }

        $yamlData = $validationResult['yamlData'];
        
        // Check for existing rule with same title
        $existingRule = $this->entityManger->getRepository(SigmaRule::class)->findOneBy(['title' => $yamlData['title']]);
        if ($existingRule) {
            return new JsonResponse(['error' => 'A rule with this title already exists'], Response::HTTP_BAD_REQUEST);
        }

        // Create new rule and initial version
        $newRule = new SigmaRule();
        $newRuleVersion = new SigmaRuleVersion();
        $newRule->setTitle($yamlData['title']);
        $newRule->setDescription($yamlData['description']);
        $newRule->setActive(false);  // New rules start inactive
        $newRuleVersion->setContent(Yaml::dump($yamlData, 4));
        $newRuleVersion->setLevel($yamlData['level']);
        $newRule->addVersion($newRuleVersion);

        // Check for existing version with same content hash
        $existingVersion = $this->entityManger->getRepository(SigmaRuleVersion::class)->findOneBy(['hash' => $newRuleVersion->getHash()]);
        if ($existingVersion) {
            return new JsonResponse(['error' => 'A rule with the exact same content already exists'], Response::HTTP_BAD_REQUEST);
        }

        // Persist new rule and version to database
        try{
            $this->entityManger->persist($newRule);
            $this->entityManger->flush();
        } catch (\Exception $e) {
            return  new JsonResponse(['error' => 'Failed to save the rule: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['error' => '', 'rule_id' => $newRule->getId()], Response::HTTP_OK);
    }


    /**
     * List all Sigma rules with summary information.
     * 
     * Retrieves a summary of all Sigma rules including basic metadata
     * such as title, description, status, and version count.
     * 
     * @param Request $request HTTP request
     * 
     * @return Response JSON array of rule summaries
     * 
     * Response format:
     * [
     *   {
     *     "id": number,
     *     "title": string,
     *     "description": string,
     *     "active": boolean,
     *     "version_count": number,
     *     "created_at": string,
     *     "updated_at": string
     *   }
     * ]
     */
    #[Route('/api/rules/sigma/list', name: 'app_sigma_list', methods: ['GET'])]
    public function listRulesSummary(Request $request): Response
    {
        $rules = $this->entityManger->getRepository(SigmaRule::class)->summaryFindAll();
        return new JsonResponse($rules, Response::HTTP_OK);
    }

    /**
     * Update the activation status of a Sigma rule.
     * 
     * Activates or deactivates a Sigma rule, which controls whether it
     * generates alerts. When activated, converts the rule to Elastalert format.
     * When deactivated, removes the corresponding Elastalert rule.
     * 
     * @param Request $request HTTP request containing new status
     * @param int $ruleId ID of the rule to update
     * 
     * @return JsonResponse Status update result or error message
     * 
     * Request body:
     * {
     *   "active": boolean  // New activation status (required)
     * }
     * 
     * Success response (200):
     * {
     *   "message": "Rule status updated successfully"
     * }
     * 
     * Error responses:
     * - 404: Rule not found
     * - 400: Missing active status
     * - 500: Elastalert rule conversion failure
     */
    #[Route('/api/rules/sigma/{ruleId}/status', name:'app_sigma_change_rule_status', methods: ['PUT'])]
    public function editRuleStatus(Request $request, int $ruleId): JsonResponse
    {
        // Find the rule to update
        $rule = $this->entityManger->getRepository(SigmaRule::class)->find($ruleId);
        if(!$rule){
            return new JsonResponse(['error' => 'Rule not found'], Response::HTTP_NOT_FOUND);
        }
        
        // Extract new activation status
        $data = json_decode($request->getContent(), true);
        if (!isset($data['active'])) {
            return new JsonResponse(['error' => 'Missing active status'], Response::HTTP_BAD_REQUEST);
        }
        
        // Update rule activation status
        $rule->setActive($data['active']);

        // Handle Elastalert rule management based on activation status
        if(!$data['active']){
            // Remove Elastalert rule when deactivating
            $this->elastalertValidator->removeElastalertRule($rule->getRuleLatestVersion());
        }else{
            // Create/update Elastalert rule when activating
            $e = $this->elastalertValidator->createElastalertRule($rule->getRuleLatestVersion());
            if (isset($e['error'])) {
                return new JsonResponse(['error' => $e['error']], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        // Save changes to database
        $this->entityManger->flush();
        return new JsonResponse(['message' => 'Rule status updated successfully'], Response::HTTP_OK);
    }

    /**
     * Get detailed information for a specific Sigma rule.
     * 
     * Retrieves complete details for a Sigma rule including all versions,
     * metadata, and content. Returns serialized rule data with detailed information.
     * 
     * @param Request $request HTTP request
     * @param int $ruleId ID of the rule to retrieve
     * 
     * @return JsonResponse Complete rule details or error message
     * 
     * Success response (200): Serialized rule data with 'rule_details' group
     * Error response (404): {"error": "Rule not found"}
     */
    #[Route('api/rules/sigma/{ruleId}/details', name:'app_sigma_get_rule', methods: ['GET'])]
    public function getRule(Request $request, int $ruleId): JsonResponse
    {
        $rule = $this->entityManger->getRepository(SigmaRule::class)->find($ruleId);
        if (!$rule) {
            return new JsonResponse(['error' => 'Rule not found'], Response::HTTP_NOT_FOUND);
        }
        
        $serializedRule = $this->serializer->serialize($rule, 'json', ['groups' => ['rule_details']]);
        return new JsonResponse($serializedRule, Response::HTTP_OK, [], true);
    }

    /**
     * Add a new version to an existing Sigma rule.
     * 
     * Creates a new version of an existing Sigma rule with updated content.
     * Validates the new content, prevents duplicate versions, and updates
     * the Elastalert rule if the parent rule is active.
     * 
     * @param Request $request HTTP request containing new rule content
     * @param int $ruleId ID of the rule to add version to
     * 
     * @return JsonResponse Version creation result or validation errors
     * 
     * Request body:
     * {
     *   "rule_content": string  // YAML content of the new rule version (required)
     * }
     * 
     * Success response (200):
     * {
     *   "error": "",
     *   "version_id": number  // ID of the created version
     * }
     * 
     * Error responses:
     * - 404: Rule not found
     * - 400: Missing content, validation errors, duplicate content/title
     * - 500: Database save or Elastalert conversion failure
     */
    #[Route('/api/rules/sigma/{ruleId}/add_version', name: 'app_sigma_add_version', methods: ['POST'])]
    public function addRuleVersion(Request $request, int $ruleId): JsonResponse
    {
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

        if($rule->isActive()){
            $this->elastalertValidator->removeElastalertRule($latestVersion);
        }

        try {
            $this->entityManger->persist($newRuleVersion);
            $this->entityManger->flush();
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to save the new version: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $e = $this->elastalertValidator->createElastalertRule($newRuleVersion);
        if (isset($e['error'])) {
            return new JsonResponse(['error' => $e['error']], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['error' => '', 'version_id' => $newRuleVersion->getId()], Response::HTTP_OK);
    }

    /**
     * Delete a Sigma rule and all its versions.
     * 
     * Permanently removes a Sigma rule and all associated versions from the system.
     * If the rule is active, also removes the corresponding Elastalert rule.
     * This operation cannot be undone.
     * 
     * @param Request $request HTTP request
     * @param int $ruleId ID of the rule to delete
     * 
     * @return JsonResponse Deletion result or error message
     * 
     * Success response (200):
     * {
     *   "message": "Rule deleted successfully"
     * }
     * 
     * Error response (404): {"error": "Rule not found"}
     */
    #[Route('api/rules/sigma/{ruleId}', name:'app_sigma_delete_rule', methods: ['DELETE'])]
    public function deleteRule(Request $request, int $ruleId): JsonResponse
    {
        $rule = $this->entityManger->getRepository(SigmaRule::class)->find($ruleId);
        if (!$rule) {
            return new JsonResponse(['error' => 'Rule not found'], Response::HTTP_NOT_FOUND);
        }

        if($rule->isActive()){
            $this->elastalertValidator->removeElastalertRule($rule->getRuleLatestVersion());
        }

        $this->entityManger->remove($rule);
        $this->entityManger->flush();

        return new JsonResponse(['message' => 'Rule deleted successfully'], Response::HTTP_OK);
    }
}