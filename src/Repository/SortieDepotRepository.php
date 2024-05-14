<?php

namespace App\Repository;

use App\Entity\SortieDepot;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SortieDepot>
 *
 * @method SortieDepot|null find($id, $lockMode = null, $lockVersion = null)
 * @method SortieDepot|null findOneBy(array $criteria, array $orderBy = null)
 * @method SortieDepot[]    findAll()
 * @method SortieDepot[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SortieDepotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SortieDepot::class);
    }

    public function save(SortieDepot $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(SortieDepot $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findAllOrderedByDate()
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.releaseDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

//    /**
//     * @return SortieDepot[] Returns an array of SortieDepot objects
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

//    public function findOneBySomeField($value): ?SortieDepot
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
