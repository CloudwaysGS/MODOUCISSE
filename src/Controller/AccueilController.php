<?php

namespace App\Controller;

use App\Repository\ChargementRepository;
use App\Repository\EntreeRepository;
use App\Repository\ProduitRepository;
use App\Repository\SortieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AccueilController extends AbstractController
{
    #[Route('/accueil', name: 'accueil')]
    public function index(ProduitRepository $prod,
                          SortieRepository $sort,
                          EntreeRepository $entree,
                          ChargementRepository $charge,
                            EntityManagerInterface $entityManager
    ): Response
    {

        //Compte nombre de produit
        $total = $prod->createQueryBuilder('p')
            ->select('COALESCE(COUNT(p.id), 0)')
            ->getQuery()
            ->getSingleScalarResult();

        $entreetotal24H = 0;
        $entreetotal = 0;

        // Date et heure actuelle
        $currentDateTime = new \DateTime();
        // Réinitialisation à minuit
        $currentDateTime->setTime(0, 0, 0);

        // Date et heure il y a 24 heures
        $twentyFourHoursAgo = clone $currentDateTime;
        $twentyFourHoursAgo->modify('-24 hours');

        // Total des sorties effectuées depuis les dernières 24 heures jusqu'à maintenant
        $sortie24H = $sort->createQueryBuilder('s')
            ->select('COALESCE(SUM(s.total), 0)')
            ->where('s.dateSortie >= :today')
            ->setParameter('today', $currentDateTime)
            ->getQuery()
            ->getSingleScalarResult();

        // Total des produits achetés depuis minuit aujourd'hui (réinitialisation)
        $sumTotal24H = $charge->createQueryBuilder('c')
            ->select('COALESCE(SUM(c.total), 0)')
            ->where('c.date >= :today')
            ->setParameter('today', $currentDateTime)
            ->getQuery()
            ->getSingleScalarResult();

        // Somme totale des sorties des 24 dernières heures et des produits achetés depuis minuit aujourd'hui
        $sortietotal24H = $sortie24H + $sumTotal24H;


        // obtenir la date de début et de fin du mois en cours
        $firstDayOfMonth = date('Y-m-01');
        $lastDayOfMonth = date('Y-m-t');

// Vérifier si la date actuelle est égale à la dernière journée du mois

            $sumTotalMonth = $charge->createQueryBuilder('c')
                ->select('COALESCE(SUM(c.total), 0)')
                ->where('c.date BETWEEN :startOfMonth AND :endOfMonth')
                ->setParameter('startOfMonth', $firstDayOfMonth)
                ->setParameter('endOfMonth', $lastDayOfMonth . ' 23:59:59')
                ->getQuery()
                ->getSingleScalarResult();

            $sortieTotalMonthQuery = $sort->createQueryBuilder('s')
                ->select('COALESCE(SUM(s.total), 0)')
                ->where('s.dateSortie BETWEEN :startOfMonth AND :endOfMonth')
                ->setParameter('startOfMonth', $firstDayOfMonth)
                ->setParameter('endOfMonth', $lastDayOfMonth . ' 23:59:59')
                ->getQuery();

            $sortieTotalMonth = $sortieTotalMonthQuery->getSingleScalarResult();
            $sortieTotalMonth += $sumTotalMonth;



        // Somme totale des entrées des dernières 24 heures
        $twentyFourHoursAgo = new \DateTime('-24 hours');
        $entreetotal24H = $entree->createQueryBuilder('e')
            ->select('COALESCE(SUM(e.total), 0)')
            ->where('e.dateEntree >= :twentyFourHoursAgo')
            ->setParameter('twentyFourHoursAgo', $twentyFourHoursAgo)
            ->getQuery()
            ->getSingleScalarResult();

       
        return $this->render('accueil.html.twig', [
            'controller_name' => 'AccueilController',
            'total' => $total,
            'sortieTotalMonth' => $sortieTotalMonth,
            'sortietotal24H' => $sortietotal24H,
            'entreetotal24H' => $entreetotal24H,
        
        ]);

    }
}
