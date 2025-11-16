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
     * Récupère toutes les SigmaRules en joignant uniquement la SigmaRuleVersion la plus récente (basée sur createdOn).
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

    //    /**
    //     * @return SigmaRule[] Returns an array of SigmaRule objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?SigmaRule
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
