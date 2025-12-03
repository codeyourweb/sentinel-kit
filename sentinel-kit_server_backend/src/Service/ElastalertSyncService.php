<?php

namespace App\Service;

use App\Entity\Alert;
use App\Entity\SigmaRule;
use App\Entity\SigmaRuleVersion;
use App\Service\ElasticsearchService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ElastalertSyncService
{
    private EntityManagerInterface $entityManager;
    private ElasticsearchService $elasticsearchService;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        ElasticsearchService $elasticsearchService,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->elasticsearchService = $elasticsearchService;
        $this->logger = $logger;
    }

    public function syncElastalertAlerts(\DateTime $since = null): array
    {
        if (!$since) {
            $since = new \DateTime('-1 hour');
        }

        $stats = [
            'processed' => 0,
            'created' => 0,
            'errors' => 0,
            'skipped' => 0
        ];

        try {
            $query = [
                'index' => 'elastalert_status',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['term' => ['alert_sent' => true]],
                                ['range' => [
                                    '@timestamp' => [
                                        'gte' => $since->format('Y-m-d\TH:i:s\Z')
                                    ]
                                ]]
                            ]
                        ]
                    ],
                    'sort' => [
                        '@timestamp' => ['order' => 'asc']
                    ],
                    'size' => 1000
                ]
            ];

            $response = $this->elasticsearchService->search($query);
            
            if (!isset($response['hits']['hits'])) {
                $this->logger->warning('No ElastAlert alerts found');
                return $stats;
            }

            foreach ($response['hits']['hits'] as $hit) {
                $stats['processed']++;
                $result = $this->processElastalertAlert($hit);
                $stats[$result]++;
            }

            $this->entityManager->flush();

        } catch (\Exception $e) {
            $this->logger->error('Error syncing ElastAlert alerts: ' . $e->getMessage());
            $stats['errors']++;
        }

        return $stats;
    }

    private function processElastalertAlert(array $elastalertHit): string
    {
        try {
            $source = $elastalertHit['_source'];
            $elastalertId = $elastalertHit['_id'];
            
            $existingAlert = $this->entityManager->getRepository(Alert::class)
                ->findOneBy(['elastic_document' => $elastalertId]);
            
            if ($existingAlert) {
                return 'skipped';
            }

            $ruleName = $source['rule_name'] ?? null;
            if (!$ruleName) {
                $this->logger->warning('ElastAlert alert missing rule_name', ['id' => $elastalertId]);
                return 'errors';
            }

            $sigmaRule = $this->entityManager->getRepository(SigmaRule::class)
                ->findOneBy(['title' => $ruleName]);

            if (!$sigmaRule) {
                $this->logger->warning('No SigmaRule found for rule_name: ' . $ruleName, ['id' => $elastalertId]);
                return 'errors';
            }

            $latestVersion = $sigmaRule->getRuleLatestVersion();
            if (!$latestVersion) {
                $this->logger->warning('No version found for SigmaRule: ' . $ruleName, ['id' => $elastalertId]);
                return 'errors';
            }

            $alert = new Alert();
            $alert->setRule($sigmaRule);
            $alert->setSigmaRuleVersion($latestVersion);
            $alert->setElasticDocument($elastalertId);
            
            $matchTime = $source['match_time'] ?? $source['@timestamp'] ?? null;
            if ($matchTime) {
                $eventDate = new \DateTime($matchTime);
                $alert->setEventCreatedAt($eventDate);
            }

            $this->entityManager->persist($alert);
            
            return 'created';

        } catch (\Exception $e) {
            $this->logger->error('Error processing ElastAlert alert: ' . $e->getMessage(), [
                'elastalert_id' => $elastalertId ?? 'unknown'
            ]);
            return 'errors';
        }
    }

    public function markElastalertAsProcessed(string $elastalertId): bool
    {
        try {
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Error marking ElastAlert as processed: ' . $e->getMessage());
            return false;
        }
    }

    public function getAlertStats(\DateTime $since = null): array
    {
        if (!$since) {
            $since = new \DateTime('-24 hours');
        }

        $qb1 = $this->entityManager->createQueryBuilder();
        
        $total = $qb1->select('COUNT(a.id)')
            ->from(Alert::class, 'a')
            ->where('a.createdOn >= :since')
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();

        $qb2 = $this->entityManager->createQueryBuilder();
        
        $byRule = $qb2->select('r.title as rule_title, COUNT(a.id) as alert_count')
            ->from(Alert::class, 'a')
            ->join('a.rule', 'r')
            ->where('a.createdOn >= :since')
            ->groupBy('r.title')
            ->orderBy('alert_count', 'DESC')
            ->setParameter('since', $since)
            ->getQuery()
            ->getResult();

        return [
            'total_alerts' => $total,
            'alerts_by_rule' => $byRule,
            'since' => $since->format('Y-m-d H:i:s')
        ];
    }
}