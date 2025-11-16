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
        $qb = $this->createQueryBuilder("r")->orderBy("r.title","ASC")->select("r.id","r.title","r.description","r.active","r.createdOn","r.createdOn");
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
