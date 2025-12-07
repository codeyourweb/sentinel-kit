<?php

namespace App\Controller;

use App\Entity\Alert;
use App\Service\ElastalertSyncService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/alerts')]
class AlertController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private SerializerInterface $serializer;
    private ElastalertSyncService $elastalertSyncService;

    public function __construct(
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        ElastalertSyncService $elastalertSyncService
    ) {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->elastalertSyncService = $elastalertSyncService;
    }

    #[Route('', name: 'app_alerts_list', methods: ['GET'])]
    public function listAlerts(Request $request): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(1, $request->query->getInt('limit', 20)));
        $since = $request->query->get('since');
        $startDate = $request->query->get('startDate');
        $endDate = $request->query->get('endDate');
        $filter = $request->query->get('filter');
        
        $qb = $this->entityManager->getRepository(Alert::class)->createQueryBuilder('a')
            ->leftJoin('a.rule', 'r')
            ->leftJoin('a.sigmaRuleVersion', 'v')
            ->addSelect('r', 'v')
            ->orderBy('a.createdOn', 'DESC');

        $whereConditions = [];
        $parameters = [];

        if ($startDate && $endDate) {
            try {
                $start = new \DateTime($startDate);
                $end = new \DateTime($endDate);
                $whereConditions[] = 'a.createdOn >= :startDate AND a.createdOn <= :endDate';
                $parameters['startDate'] = $start;
                $parameters['endDate'] = $end;
            } catch (\Exception $e) {
                return new JsonResponse(['error' => 'Invalid date format for startDate or endDate parameter'], Response::HTTP_BAD_REQUEST);
            }
        } elseif ($since) {
            try {
                $sinceDate = new \DateTime($since);
                $whereConditions[] = 'a.createdOn >= :since';
                $parameters['since'] = $sinceDate;
            } catch (\Exception $e) {
                return new JsonResponse(['error' => 'Invalid date format for since parameter'], Response::HTTP_BAD_REQUEST);
            }
        }

        if ($filter && trim($filter)) {
            $whereConditions[] = '(r.title LIKE :filter OR r.description LIKE :filter)';
            $parameters['filter'] = '%' . trim($filter) . '%';
        }

        if (!empty($whereConditions)) {
            $qb->where(implode(' AND ', $whereConditions));
            foreach ($parameters as $key => $value) {
                $qb->setParameter($key, $value);
            }
        }

        $offset = ($page - 1) * $limit;
        $qb->setFirstResult($offset)->setMaxResults($limit);

        $alerts = $qb->getQuery()->getResult();
        
        $totalQb = $this->entityManager->getRepository(Alert::class)->createQueryBuilder('a')
            ->select('COUNT(a.id)');
        
        if ($startDate && $endDate) {
            $start = new \DateTime($startDate);
            $end = new \DateTime($endDate);
            $totalQb->where('a.createdOn >= :startDate AND a.createdOn <= :endDate')
                    ->setParameter('startDate', $start)
                    ->setParameter('endDate', $end);
        } elseif ($since) {
            $sinceDate = new \DateTime($since);
            $totalQb->where('a.createdOn >= :since')
                    ->setParameter('since', $sinceDate);
        }
        
        $total = $totalQb->getQuery()->getSingleScalarResult();

        $serializedAlerts = [];
        foreach ($alerts as $alert) {
            $serializedAlerts[] = [
                'id' => $alert->getId(),
                'rule' => [
                    'id' => $alert->getRule()->getId(),
                    'title' => $alert->getRule()->getTitle(),
                    'description' => $alert->getRule()->getDescription(),
                    'active' => $alert->getRule()->isActive()
                ],
                'rule_version' => [
                    'id' => $alert->getSigmaRuleVersion()->getId(),
                    'level' => $alert->getSigmaRuleVersion()->getLevel()
                ],
                'elastic_document_id' => $alert->getElasticDocument(),
                'event_timestamp' => $alert->getEventCreatedAt()?->format('c'),
                'created_at' => $alert->getCreatedOn()->format('c')
            ];
        }

        return new JsonResponse([
            'alerts' => $serializedAlerts,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int)$total,
                'pages' => (int)ceil($total / $limit)
            ]
        ]);
    }

    #[Route('/{id}', name: 'app_alert_details', methods: ['GET'])]
    public function getAlert(int $id): JsonResponse
    {
        $alert = $this->entityManager->getRepository(Alert::class)->find($id);
        
        if (!$alert) {
            return new JsonResponse(['error' => 'Alert not found'], Response::HTTP_NOT_FOUND);
        }

        $alertData = [
            'id' => $alert->getId(),
            'rule' => [
                'id' => $alert->getRule()->getId(),
                'title' => $alert->getRule()->getTitle(),
                'description' => $alert->getRule()->getDescription(),
                'active' => $alert->getRule()->isActive(),
                'filename' => $alert->getRule()->getFilename()
            ],
            'rule_version' => [
                'id' => $alert->getSigmaRuleVersion()->getId(),
                'level' => $alert->getSigmaRuleVersion()->getLevel(),
                'content' => $alert->getSigmaRuleVersion()->getContent(),
                'created_at' => $alert->getSigmaRuleVersion()->getCreatedOn()->format('c')
            ],
            'elastic_document_id' => $alert->getElasticDocument(),
            'event_timestamp' => $alert->getEventCreatedAt()?->format('c'),
            'created_at' => $alert->getCreatedOn()->format('c')
        ];

        return new JsonResponse($alertData);
    }

    #[Route('/stats', name: 'app_alerts_stats', methods: ['GET'])]
    public function getAlertsStats(Request $request): JsonResponse
    {
        $since = $request->query->get('since', '-24 hours');
        
        try {
            $sinceDate = new \DateTime($since);
            $stats = $this->elastalertSyncService->getAlertStats($sinceDate);
            
            return new JsonResponse($stats);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Invalid date format: ' . $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/sync', name: 'app_alerts_sync', methods: ['POST'])]
    public function syncAlerts(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $since = $data['since'] ?? '-1 hour';
        
        try {
            $sinceDate = new \DateTime($since);
            $stats = $this->elastalertSyncService->syncElastalertAlerts($sinceDate);
            
            return new JsonResponse([
                'message' => 'Synchronization completed',
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Synchronization failed: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/rules/{ruleId}/alerts', name: 'app_rule_alerts', methods: ['GET'])]
    public function getAlertsForRule(int $ruleId, Request $request): JsonResponse
    {
        $rule = $this->entityManager->getRepository(\App\Entity\SigmaRule::class)->find($ruleId);
        
        if (!$rule) {
            return new JsonResponse(['error' => 'Rule not found'], Response::HTTP_NOT_FOUND);
        }

        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(1, $request->query->getInt('limit', 20)));
        
        $qb = $this->entityManager->getRepository(Alert::class)->createQueryBuilder('a')
            ->leftJoin('a.sigmaRuleVersion', 'v')
            ->addSelect('v')
            ->where('a.rule = :rule')
            ->setParameter('rule', $rule)
            ->orderBy('a.createdOn', 'DESC');

        $offset = ($page - 1) * $limit;
        $qb->setFirstResult($offset)->setMaxResults($limit);

        $alerts = $qb->getQuery()->getResult();
        
        $totalQb = $this->entityManager->getRepository(Alert::class)->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.rule = :rule')
            ->setParameter('rule', $rule);
        
        $total = $totalQb->getQuery()->getSingleScalarResult();

        $serializedAlerts = [];
        foreach ($alerts as $alert) {
            $serializedAlerts[] = [
                'id' => $alert->getId(),
                'elastic_document_id' => $alert->getElasticDocument(),
                'event_timestamp' => $alert->getEventCreatedAt()?->format('c'),
                'created_at' => $alert->getCreatedOn()->format('c'),
                'rule_version' => [
                    'id' => $alert->getSigmaRuleVersion()->getId(),
                    'level' => $alert->getSigmaRuleVersion()->getLevel()
                ]
            ];
        }

        return new JsonResponse([
            'rule' => [
                'id' => $rule->getId(),
                'title' => $rule->getTitle()
            ],
            'alerts' => $serializedAlerts,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int)$total,
                'pages' => (int)ceil($total / $limit)
            ]
        ]);
    }
    
    #[Route('/test/elasticsearch', name: 'alerts_test_elasticsearch', methods: ['GET'])]
    public function testElasticsearch(): JsonResponse
    {
        try {
            $stats = $this->elastalertSyncService->getAlertStats();
            
            return new JsonResponse([
                'status' => 'success',
                'message' => 'Connexion Elasticsearch réussie',
                'elasticsearch_status' => $stats
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Erreur de connexion Elasticsearch',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}