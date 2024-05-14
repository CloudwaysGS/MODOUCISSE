<?php

namespace App\Repository;

use App\Entity\EntreeDepot;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EntreeDepot>
 *
 * @method EntreeDepot|null find($id, $lockMode = null, $lockVersion = null)
 * @method EntreeDepot|null findOneBy(array $criteria, array $orderBy = null)
 * @method EntreeDepot[]    findAll()
 * @method EntreeDepot[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EntreeDepotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EntreeDepot::class);
    }

    public function save(EntreeDepot $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(EntreeDepot $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findAllOrderedByDate()
    {
        return $this->createQueryBuilder('e')
            ->orderBy('e.releaseDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

//    /**
//     * @return EntreeDepot[] Returns an array of EntreeDepot objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('e.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?EntreeDepot
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
