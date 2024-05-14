<?php

namespace App\Repository;

use App\Entity\Dette;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Dette>
 *
 * @method Dette|null find($id, $lockMode = null, $lockVersion = null)
 * @method Dette|null findOneBy(array $criteria, array $orderBy = null)
 * @method Dette[]    findAll()
 * @method Dette[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DetteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dette::class);
    }

    public function save(Dette $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Dette $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findAllOrderedByDate()
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.statut', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }

    public function findUnpaidDebtsTotal()
    {
        return $this->createQueryBuilder('p')
            ->select('SUM(p.montantDette) as totalAmount')
            ->where('p.statut = :unpaid')
            ->setParameter('unpaid', 'impayé')
            ->getQuery()
            ->getSingleScalarResult();
    }


    public function findByName($nom)
    {
        return $this->createQueryBuilder('p')
            ->join('p.client', 'c')
            ->andWhere('c.nom LIKE :nom')
            ->setParameter('nom', '%'.$nom.'%')
            ->orderBy('p.dateCreated', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findSumMontantImpaye()
    {
        return $this->createQueryBuilder('d')
            ->select('SUM(d.reste)')
            ->where('d.statut = :statut')
            ->setParameter('statut', 'impayé')
            ->getQuery()
            ->getSingleScalarResult();
    }

//    /**
//     * @return Dette[] Returns an array of Dette objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('d')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('d.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Dette
//    {
//        return $this->createQueryBuilder('d')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
