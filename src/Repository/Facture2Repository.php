<?php

namespace App\Repository;

use App\Entity\Facture2;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Facture2>
 *
 * @method Facture2|null find($id, $lockMode = null, $lockVersion = null)
 * @method Facture2|null findOneBy(array $criteria, array $orderBy = null)
 * @method Facture2[]    findAll()
 * @method Facture2[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class Facture2Repository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Facture2::class);
    }

    public function save(Facture2 $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Facture2 $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findAllOrderedByDate()
    {
        return $this->createQueryBuilder('f')
            ->orderBy('f.date', 'DESC')
            ->where('f.etat = :etat')
            ->setParameter('etat', '1')
            ->getQuery()
            ->getResult();
    }
//    /**
//     * @return Facture2[] Returns an array of Facture2 objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('f.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Facture2
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
