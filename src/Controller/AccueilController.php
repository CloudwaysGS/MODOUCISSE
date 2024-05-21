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

        // Fonction générique pour récupérer les ventes sur une période donnée
        function getSalesData($charge, $start, $end) {
            $queryBuilder = $charge->createQueryBuilder('c')
                ->select('
            COALESCE(SUM(c.total), 0) AS totalSold,
            COALESCE(COUNT(c.id), 0) AS salesCount,
            COALESCE(MAX(c.total), 0) AS maxSale,
            COALESCE(MIN(c.total), 0) AS minSale
        ')
                ->where('c.date >= :start')
                ->andWhere('c.date <= :end')
                ->setParameter('start', $start)
                ->setParameter('end', $end);

            return $queryBuilder->getQuery()->getSingleResult();
        }

// Pagination des jours
        function getPaginatedDailySales($charge, $page, $itemsPerPage, $totalDays) {
            $offset = ($page - 1) * $itemsPerPage;
            $totalsByDate = [];

            for ($i = $offset; $i < $offset + $itemsPerPage && $i < $totalDays; $i++) {
                $date = (new DateTime())->sub(new DateInterval('P' . $i . 'D'))->format('Y-m-d');
                $startOfDay = new DateTime($date . ' 00:00:00');
                $endOfDay = new DateTime($date . ' 23:59:59');
                $result = getSalesData($charge, $startOfDay, $endOfDay);

                $totalsByDate[] = [
                    'date' => $date,
                    'totalSold' => $result['totalSold'],
                    'salesCount' => $result['salesCount'],
                    'maxSale' => $result['maxSale'],
                    'minSale' => $result['minSale'],
                ];
            }

            $totalPages = ceil($totalDays / $itemsPerPage);

            return [$totalsByDate, $totalPages];
        }

// Pagination des mois
        function getPaginatedMonthlySales($charge, $page, $itemsPerPage, $totalMonths) {
            $offset = ($page - 1) * $itemsPerPage;
            $totalsByMonth = [];

            for ($i = $offset; $i < $offset + $itemsPerPage && $i < $totalMonths; $i++) {
                $startOfMonth = (new DateTime("first day of -$i month"))->setTime(0, 0, 0);
                $endOfMonth = (new DateTime("last day of -$i month"))->setTime(23, 59, 59);
                $result = getSalesData($charge, $startOfMonth, $endOfMonth);

                $totalsByMonth[] = [
                    'month' => $startOfMonth->format('Y-m'),
                    'totalSold' => $result['totalSold'],
                    'salesCount' => $result['salesCount'],
                    'maxSale' => $result['maxSale'],
                    'minSale' => $result['minSale'],
                ];
            }

            $totalPages = ceil($totalMonths / $itemsPerPage);

            return [$totalsByMonth, $totalPages];
        }

        // Pagination des années
        function getPaginatedYearlySales($charge, $page, $itemsPerPage, $totalYears) {
            $offset = ($page - 1) * $itemsPerPage;
            $totalsByYear = [];

            for ($i = $offset; $i < $offset + $itemsPerPage && $i < $totalYears; $i++) {
                $year = (new DateTime())->sub(new DateInterval('P' . $i . 'Y'))->format('Y');
                $startOfYear = new DateTime($year . '-01-01 00:00:00');
                $endOfYear = new DateTime($year . '-12-31 23:59:59');
                $result = getSalesData($charge, $startOfYear, $endOfYear);

                $totalsByYear[] = [
                    'year' => $year,
                    'totalSold' => $result['totalSold'],
                    'salesCount' => $result['salesCount'],
                    'maxSale' => $result['maxSale'],
                    'minSale' => $result['minSale'],
                ];
            }

            $totalPages = ceil($totalYears / $itemsPerPage);

            return [$totalsByYear, $totalPages];
        }

        // Exemple d'utilisation dans le contrôleur
        $page = $request->query->getInt('page', 1);
        $itemsPerPage = 5;

        // Ventes par jour
        $totalDays = 100;
        list($totalsByDate, $totalDailyPages) = getPaginatedDailySales($charge, $page, $itemsPerPage, $totalDays);

        // Ventes par mois
        $totalMonths = 12;
        list($totalsByMonth, $totalMonthlyPages) = getPaginatedMonthlySales($charge, $page, $itemsPerPage, $totalMonths);

        // Ventes par année
        $totalYears = 10;
        list($totalsByYear, $totalYearlyPages) = getPaginatedYearlySales($charge, $page, $itemsPerPage, $totalYears);


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
            'totalsByMonth' => $totalsByMonth,
            'totalsByYear' => $totalsByYear,
            'totalDailyPages' => $totalDailyPages,
            'totalMonthlyPages' => $totalMonthlyPages,
            'totalYearlyPages' => $totalYearlyPages,
            'benefice' => $benefice,
        ]);

    }
}
