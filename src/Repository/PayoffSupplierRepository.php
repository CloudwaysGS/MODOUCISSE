<?php

namespace App\Repository;

use App\Entity\PayoffSupplier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PayoffSupplier>
 *
 * @method PayoffSupplier|null find($id, $lockMode = null, $lockVersion = null)
 * @method PayoffSupplier|null findOneBy(array $criteria, array $orderBy = null)
 * @method PayoffSupplier[]    findAll()
 * @method PayoffSupplier[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PayoffSupplierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PayoffSupplier::class);
    }

    public function save(PayoffSupplier $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PayoffSupplier $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findAllOrderedByDate($limit, $offset)
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.datePaiement', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countAll(): int
    {
        $qb = $this->createQueryBuilder('p');
        $qb->select('COUNT(p)');
        $query = $qb->getQuery();
        return $query->getSingleScalarResult();
    }

    // src/Repository/ProduitRepository.php

    public function findByName($nom)
    {
        return $this->createQueryBuilder('p')
            ->join('p.fournisseur', 'c')
            ->andWhere('c.nom LIKE :nom')
            ->setParameter('nom', '%'.$nom.'%')
            ->orderBy('p.datePaiement', 'DESC')
            ->getQuery()
            ->getResult();
    }
//    /**
//     * @return PayoffSupplier[] Returns an array of PayoffSupplier objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?PayoffSupplier
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
