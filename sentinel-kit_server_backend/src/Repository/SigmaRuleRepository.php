<?php

namespace App\Repository;

use App\Entity\SigmaRule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SigmaRule>
 */
class SigmaRuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SigmaRule::class);
    }

    /**
     * Retrieve all sigma rules as array object without content
     * @return array
     */
    public function summaryFindAll() : array{
        $qb = $this->createQueryBuilder("r");

        $subQuery = $this->createQueryBuilder('r2')
            ->select('MAX(v2.createdOn)')
            ->innerJoin('r2.versions', 'v2')
            ->where('r2.id = r.id') 
            ->getDQL();

        $alertsCount24h = $this->createQueryBuilder('r3')
            ->select('COUNT(a24.id)')
            ->innerJoin('r3.alerts', 'a24')
            ->where('r3.id = r.id')
            ->andWhere('a24.createdOn >= :date24h')
            ->getDQL();

        $alertsCount7d = $this->createQueryBuilder('r4')
            ->select('COUNT(a7.id)')
            ->innerJoin('r4.alerts', 'a7')
            ->where('r4.id = r.id')
            ->andWhere('a7.createdOn >= :date7d')
            ->getDQL();

        $alertsCount30d = $this->createQueryBuilder('r5')
            ->select('COUNT(a30.id)')
            ->innerJoin('r5.alerts', 'a30')
            ->where('r5.id = r.id')
            ->andWhere('a30.createdOn >= :date30d')
            ->getDQL();

        $qb->leftJoin(
                'r.versions',
                'v', 
                Join::WITH, 
                $qb->expr()->eq('v.createdOn', '(' . $subQuery . ')')
            )
            ->select(
                "r.id",
                "r.title",
                "r.description",
                "r.active",
                "r.createdOn",
                "v.level",
                "(" . $alertsCount24h . ") as alerts_24h",
                "(" . $alertsCount7d . ") as alerts_7d",
                "(" . $alertsCount30d . ") as alerts_30d"
            )
            ->setParameter('date24h', new \DateTime('-24 hours'))
            ->setParameter('date7d', new \DateTime('-7 days'))
            ->setParameter('date30d', new \DateTime('-30 days'))
            ->orderBy("r.title","ASC");
            
        return $qb->getQuery()->getResult();
    }

    /**
     * Retrieves all SigmaRules joining only the most recent SigmaRuleVersion (based on createdOn).
     *
     * @return SigmaRule[]
     */
    public function findAllWithLatestRuleVersion(): array
    {
        $qb = $this->createQueryBuilder('r');

        $subQuery = $this->createQueryBuilder('r2')
            ->select('MAX(v2.createdOn)')
            ->innerJoin('r2.versions', 'v2')
            ->where('r2.id = r.id') 
            ->getDQL();

        $qb->leftJoin(
                'r.versions',
                'v', 
                Join::WITH, 
                $qb->expr()->eq('v.createdOn', '(' . $subQuery . ')')
            )
            ->addSelect('v')
            ->orderBy('r.title', 'ASC');
        return $qb->getQuery()->getResult();
    }
}
