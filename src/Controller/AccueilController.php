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
use DateTime;
use DateInterval;
use Doctrine\ORM\Query\Expr;


class AccueilController extends AbstractController
{
    #[Route('/accueil', name: 'accueil')]
    public function index(ProduitRepository $prod,
                          SortieRepository $sort,
                          EntreeRepository $entree,
                          ChargementRepository $charge,
    ): Response
    {

        //Compte nombre de produit
        $total = $prod->createQueryBuilder('p')
            ->select('COALESCE(COUNT(p.id), 0)')
            ->getQuery()
            ->getSingleScalarResult();

        $totalChargements = $charge->getTotalChargements();
        $totalEntrees = $entree->findTotalEntrées();
        $benefice = $totalChargements - $totalEntrees;

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
        /*$sortie24H = $sort->createQueryBuilder('s')
            ->select('COALESCE(SUM(s.total), 0)')
            ->where('s.dateSortie >= :today')
            ->setParameter('today', $currentDateTime)
            ->getQuery()
            ->getSingleScalarResult();*/

        // Total des produits achetés depuis minuit aujourd'hui (réinitialisation)
        $sumTotal24H = $charge->createQueryBuilder('c')
            ->select('COALESCE(SUM(c.total), 0)')
            ->where('c.date >= :today')
            ->setParameter('today', $currentDateTime)
            ->getQuery()
            ->getSingleScalarResult();

        // Créer un tableau pour stocker les totaux vendus par jour
        $totalsByDate = [];

        // Boucler sur les dix derniers jours pour récupérer les totaux vendus pour chaque jour
        for ($i = 0; $i < 5; $i++) {
            // Calculer la date du jour en cours sans modifier la date actuelle
            $date = (new DateTime())->sub(new DateInterval('P' . $i . 'D'))->format('Y-m-d');

            // Récupérer la somme vendue pour cette date
            $queryBuilder = $charge->createQueryBuilder('c')
                ->select('
            COALESCE(SUM(c.total), 0) AS totalSold,
            COALESCE(COUNT(c.id), 0) AS salesCount,
            COALESCE(MAX(c.total), 0) AS maxSale,
            COALESCE(MIN(c.total), 0) AS minSale
        ')
                ->where('c.date >= :startOfDay')
                ->andWhere('c.date < :endOfDay')
                ->setParameter('startOfDay', new DateTime($date . ' 00:00:00'))
                ->setParameter('endOfDay', new DateTime($date . ' 23:59:59'));

            // Exécuter la requête et obtenir les résultats
            $result = $queryBuilder->getQuery()->getSingleResult();

            // Ajouter la somme vendue au tableau avec la date correspondante
            $totalsByDate[] = [
                'date' => $date,
                'totalSold' => $result['totalSold'],
                'salesCount' => $result['salesCount'],
                'maxSale' => $result['maxSale'],
                'minSale' => $result['minSale'],
            ];
        }

        // Somme totale des sorties des 24 dernières heures et des produits achetés depuis minuit aujourd'hui
        //$sortietotal24H = $sortie24H + $sumTotal24H;


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
            'sumTotal24H' => $sumTotal24H,
            'entreetotal24H' => $entreetotal24H,
            'totalsByDate' => $totalsByDate,
            'benefice' => $benefice,
        ]);

    }
}
