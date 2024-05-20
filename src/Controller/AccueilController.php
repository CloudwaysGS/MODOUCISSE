<?php

namespace App\Controller;

use App\Repository\ChargementRepository;
use App\Repository\EntreeRepository;
use App\Repository\ProduitRepository;
use App\Repository\SortieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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
                            Request $request,
    ): Response
    {

        // Date et heure actuelle
        $currentDateTime = new \DateTime();
        // Réinitialisation à minuit
        $currentDateTime->setTime(0, 0, 0);

        // Date et heure il y a 24 heures
        $twentyFourHoursAgo = clone $currentDateTime;
        $twentyFourHoursAgo->modify('-24 hours');

        // Total des produits achetés depuis minuit aujourd'hui (réinitialisation)
        $sumTotal24H = $charge->createQueryBuilder('c')
            ->select('COALESCE(SUM(c.total), 0)')
            ->where('c.date >= :today')
            ->setParameter('today', $currentDateTime)
            ->getQuery()
            ->getSingleScalarResult();

        // Définir le nombre total de jours et d'éléments par page
        $totalDays = 100;
        $itemsPerPage = 5;

        $page = $request->query->getInt('page', 1);
        $offset = ($page - 1) * $itemsPerPage;

        $totalsByDate = [];

        // Boucler sur les jours de la page actuelle pour récupérer les totaux vendus pour chaque jour
        for ($i = $offset; $i < $offset + $itemsPerPage && $i < $totalDays; $i++) {
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
        // Calculer le nombre total de pages
        $totalPages = ceil($totalDays / $itemsPerPage);




        // Définir le nombre total de mois et d'éléments par page
        $totalMonths = 12;
        $itemsPerPage = 5;

        $page = $request->query->getInt('page', 1);
        $offset = ($page - 1) * $itemsPerPage;

        $totalsByMonth = [];

// Boucler sur les mois de la page actuelle pour récupérer les totaux vendus pour chaque mois
        for ($i = $offset; $i < $offset + $itemsPerPage && $i < $totalMonths; $i++) {
            // Calculer le début et la fin du mois en cours
            $startOfMonth = (new DateTime("first day of -$i month"))->setTime(0, 0, 0);
            $endOfMonth = (new DateTime("last day of -$i month"))->setTime(23, 59, 59);

            // Récupérer la somme vendue pour ce mois
            $queryBuilder = $charge->createQueryBuilder('c')
                ->select('
            COALESCE(SUM(c.total), 0) AS totalSold,
            COALESCE(COUNT(c.id), 0) AS salesCount,
            COALESCE(MAX(c.total), 0) AS maxSale,
            COALESCE(MIN(c.total), 0) AS minSale
        ')
                ->where('c.date >= :startOfMonth')
                ->andWhere('c.date <= :endOfMonth')
                ->setParameter('startOfMonth', $startOfMonth)
                ->setParameter('endOfMonth', $endOfMonth);

            // Exécuter la requête et obtenir les résultats
            $result = $queryBuilder->getQuery()->getSingleResult();

            // Ajouter la somme vendue au tableau avec le mois correspondant
            $totalsByMonth[] = [
                'month' => $startOfMonth->format('Y-m'),
                'totalSold' => $result['totalSold'],
                'salesCount' => $result['salesCount'],
                'maxSale' => $result['maxSale'],
                'minSale' => $result['minSale'],
            ];
        }

        // Calculer le nombre total de pages
        $totalPages = ceil($totalMonths / $itemsPerPage);


        // Somme totale des entrées des dernières 24 heures
        $twentyFourHoursAgo = new \DateTime('-24 hours');
        $entreetotal24H = $entree->createQueryBuilder('e')
            ->select('COALESCE(SUM(e.total), 0)')
            ->where('e.dateEntree >= :twentyFourHoursAgo')
            ->setParameter('twentyFourHoursAgo', $twentyFourHoursAgo)
            ->getQuery()
            ->getSingleScalarResult();

        $totalChargements = $charge->getTotalChargements();
        $totalEntrees = $entree->findTotalEntrées();
        $benefice = $totalChargements - $totalEntrees;

        return $this->render('accueil.html.twig', [
            'controller_name' => 'AccueilController',
            'sumTotal24H' => $sumTotal24H,
            'entreetotal24H' => $entreetotal24H,
            'totalsByDate' => $totalsByDate,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalsByMonth' => $totalsByMonth,
            'benefice' => $benefice,
        ]);

    }
}
