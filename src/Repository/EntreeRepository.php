<?php

namespace App\Repository;

use App\Entity\Entree;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Entree>
 *
 * @method Entree|null find($id, $lockMode = null, $lockVersion = null)
 * @method Entree|null findOneBy(array $criteria, array $orderBy = null)
 * @method Entree[]    findAll()
 * @method Entree[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EntreeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Entree::class);
    }

    public function save(Entree $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Entree $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findAllOrderedByDate()
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.dateEntree', 'DESC') // Modifier 'DESC' en 'ASC' pour trier par ordre croissant
            ->getQuery()
            ->getResult();
    }

    // src/Repository/EntreeRepository.php

    public function findTotalEntrées(): float
    {
        $result = $this->createQueryBuilder('e')
            ->select('SUM(e.total) as total')
            ->getQuery()
            ->getSingleScalarResult();

        // Si le résultat est null, retourner 0.0
        return $result !== null ? (float) $result : 0.0;
    }



//    /**
//     * @return Entree[] Returns an array of Entree objects
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

//    public function findOneBySomeField($value): ?Entree
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
