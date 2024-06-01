<?php

namespace App\Repository;

use App\Entity\DetteFournisseur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DetteFournisseur>
 *
 * @method DetteFournisseur|null find($id, $lockMode = null, $lockVersion = null)
 * @method DetteFournisseur|null findOneBy(array $criteria, array $orderBy = null)
 * @method DetteFournisseur[]    findAll()
 * @method DetteFournisseur[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DetteFournisseurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DetteFournisseur::class);
    }

    public function save(DetteFournisseur $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(DetteFournisseur $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findAllOrderedByDate()
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.date', 'DESC')
            ->getQuery()
            ->getResult()
            ;
    }

    public function findByName($nom)
    {
        return $this->createQueryBuilder('p')
            ->join('p.fournisseur', 'f')
            ->andWhere('f.nom LIKE :nom')
            ->setParameter('nom', '%'.$nom.'%')
            ->orderBy('p.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findNonPaidTotal()
    {
        return $this->createQueryBuilder('d')
            ->select('SUM(d.reste) as totalNonPaid')
            ->where('d.statut = :statut')
            ->setParameter('statut', 'non-payée')
            ->getQuery()
            ->getSingleScalarResult();
    }


//    /**
//     * @return DetteFournisseur[] Returns an array of DetteFournisseur objects
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

//    public function findOneBySomeField($value): ?DetteFournisseur
//    {
//        return $this->createQueryBuilder('d')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
