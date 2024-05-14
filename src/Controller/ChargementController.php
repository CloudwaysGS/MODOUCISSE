<?php

namespace App\Controller;

use App\Entity\Chargement;
use App\Entity\Client;
use App\Entity\Dette;
use App\Entity\Facture;
use App\Entity\Facture2;
use App\Entity\Produit;
use App\Entity\Search;
use App\Form\SearchType;
use App\Repository\ChargementRepository;
use App\Repository\FactureRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChargementController extends AbstractController
{
    #[Route('/chargement', name: 'liste_chargement')]
    public function index(ChargementRepository $charge, Request $request): Response
    {
        $firstDayOfMonth = new \DateTime('first day of this month');
        $lastDayOfMonth = new \DateTime('last day of this month');
        $sumTotalMonth = $charge->createQueryBuilder('c')
            ->select('SUM(c.total)')
            ->where('c.date BETWEEN :startOfMonth AND :endOfMonth')
            ->setParameter('startOfMonth', $firstDayOfMonth)
            ->setParameter('endOfMonth', $lastDayOfMonth)
            ->getQuery()
            ->getSingleScalarResult();
        $sumTotalMonth = is_null($sumTotalMonth) ? 0 : $sumTotalMonth;
        $today = new DateTimeImmutable();
        $search = new Search();
        $form2 = $this->createForm(SearchType::class, $search);
        $form2->handleRequest($request);
        $nom = $search->getNom();

        $chargement = $nom ? $charge->findByName($nom) : $charge->findAllOrderedByDate();

        $page = $request->query->getInt('page', 1);
        $limit = 10; // number of products to display per page
        $total = count($chargement);
        $offset = ($page - 1) * $limit;
        $chargement = array_slice($chargement, $offset, $limit);
        $f = null;

        return $this->render('chargement/index.html.twig', [
            'controller_name' => 'ChargementController',
            'chargement' => $chargement,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'f' => $f
        ]);
    }

    #[Route('/chargement/extraire/{id}', name: 'extraire')]
    public function extraire(Chargement $chargement)
    {
        $facture = new Facture();
        $factures = $chargement->addFacture($facture);
        foreach ($factures->getFacture() as $facture) {
            $f = $facture->getChargement()->getFacture()->toArray();
            array_pop($f);
        }
        if (!empty($f)) {
            // Récupérer le client de la dernière facture si présent, sinon récupérer le client de la première facture
            $lastFacture = end($f);
            $firstFacture = reset($f);
            $client = ($lastFacture !== false) ? $lastFacture->getClient() ?? $firstFacture->getClient() : null;
        } else {
            $facture = new Facture2();
            $factures = $chargement->addFacture2($facture);
            foreach ($factures->getFacture2s() as $facture) {
                $f = $facture->getChargement()->getFacture2s()->toArray();
                array_pop($f);
            }
            $lastFacture = end($f);
            $firstFacture = reset($f);
            $client = ($lastFacture !== false) ? $lastFacture->getClient() ?? $firstFacture->getClient() : null;
        }
        return new JsonResponse([
            'table' => $this->renderView('chargement/extraire.html.twig', ['f' => $f]),
        ]);
    }

    #[Route('/chargement/delete/{id}', name: 'chargement_delete')]
    public function delete($id, EntityManagerInterface $entityManager)
    {
        $chargements = $entityManager->getRepository(Chargement::class)->find($id);
        if (!$chargements) {
            throw $this->createNotFoundException('Chargement non trouvé');
        }

        $factures = $chargements->getFacture(); // récupérer toutes les factures associées
        foreach ($factures as $facture) {
            $entityManager->remove($facture); // supprimer chaque facture
        }
        $factures = $chargements->getFacture2s(); // récupérer toutes les factures associées
        foreach ($factures as $facture) {
            $entityManager->remove($facture); // supprimer chaque facture
        }
        $entityManager->remove($chargements); // supprimer le chargement après avoir supprimé toutes les factures associées
        $entityManager->flush();

        $this->addFlash('success', 'Le chargement a été supprimé avec succès');
        return $this->redirectToRoute('liste_chargement');
    }

    #[Route('/chargement/user/{id}', name: 'chargement_user')]
    public function user($id, EntityManagerInterface $entityManager)
    {
        $chargements = $entityManager->getRepository(Chargement::class)->find($id);
        if (!$chargements) {
            throw $this->createNotFoundException('Chargement non trouvé');
        }
        $user = $chargements->getConnect();
        
        return new JsonResponse(['user' => $user]);
    }

    #[Route('/chargement/pdf/{id}', name: 'pdf')]
    public function pdf(Chargement $chargement)
    {
        $facture = new Facture();
        $factures = $chargement->addFacture($facture);
        foreach ($factures->getFacture() as $facture) {
            $f = $facture->getChargement()->getFacture()->toArray();
            array_pop($f);
        }
        if (!empty($f)) {
            // Récupérer le client de la dernière facture si présent, sinon récupérer le client de la première facture
            $lastFacture = end($f);
            $firstFacture = reset($f);
            $client = ($lastFacture !== false) ? $lastFacture->getClient() ?? $firstFacture->getClient() : null;
            $data = [];
            $total = 0;
            foreach ($f as $facture) {
                $data[] = array(
                        'Quantité achetée' => $facture->getQuantite(),
                        'Produit' => $facture->getNomProduit(),
                        'Prix unitaire' => $facture->getPrixUnit(),
                        'Montant' => $facture->getMontant(),
                    );

                    $total += $facture->getMontant();
            }
        } else {

            $facture = new Facture2();
            $factures = $chargement->addFacture2($facture);
            foreach ($factures->getFacture2s() as $facture) {
                $f = $facture->getChargement()->getFacture2s()->toArray();
                array_pop($f);
            }
            $lastFacture = end($f);
            $firstFacture = reset($f);
            $client = ($lastFacture !== false) ? $lastFacture->getClient() ?? $firstFacture->getClient() : null;
            $data = [];
            $total = 0;

            foreach ($f as $facture) {
                $data[] = array(
                    'Quantité achetée' => $facture->getQuantite(),
                    'Produit' => $facture->getNomProduit(),
                    'Prix unitaire' => $facture->getPrixUnit(),
                    'Montant' => $facture->getMontant(),
                );

                $total += $facture->getMontant();
            }
        }

        $data[] = [
            'Quantité achetée' => '',
            'Produit' => '',
            'Prix unitaire' => '',
            'Montant total' => '',
        ];
        $headers = array(
            'Quantité',
            'Désignation',
            'Prix unitaire',
            'Montant',
        );
        $filename = $client !== null ? $client->getNom() : '';
        $filename .= date("Y-m-d_H-i", time()) . ".pdf";

        // Initialisation du PDF
        $pdf = new \FPDF();
        $pdf->AddPage();
        // Titre de la facture
        $pdf->SetFont('Arial','BI',12);
        $pdf->SetFillColor(204, 204, 204); // Couleur de fond du titre
        $pdf->SetTextColor(0, 0, 0); // Couleur du texte du titre
        $pdf->Cell(0, 10, ''.$factures->getNumeroFacture(), 0, 1, 'C', true);
        $pdf->Ln(1);

        $prenomNom = $this->getUser() ? $this->getUser()->getPrenom() . ' ' . $this->getUser()->getNom() : 'Anonyme';
        $adresse = $this->getUser() ? $this->getUser()->getAdresse() : 'Anonyme';
        $phone = $this->getUser() ? $this->getUser()->getTelephone() : 'Anonyme';
        // Informations sur le commerçant et client
        $pdf->SetFont('Arial', 'I', 9);
        $pdf->SetTextColor(51, 51, 51); // Couleur du texte des informations
        $pdf->SetFillColor(204, 204, 204); // Couleur de fond du titre
        $pdf->Cell(70, 5, 'COMMERCANT : '.$prenomNom, 0, 0, 'L');
        $pdf->Cell(120, 5, 'CLIENT : ' . ($client ? $client->getNom() : ''), 0, 1, 'R');

        $pdf->Cell(70, 5, 'ADRESSE : '.$adresse.' / Kaolack', 0, 0, 'L');
        $pdf->Cell(120, 5, 'ADRESSE : '. ($client ? $client->getAdresse() : ''), 0, 1, 'R');

        $pdf->Cell(70, 5, 'TELEPHONE : '.$phone, 0, 0, 'L');
        $pdf->Cell(120, 5, 'TELEPHONE : '. ($client ? $client->getTelephone() : ''), 0, 1, 'R');

        $pdf->Cell(70, 5, 'NINEA : 0848942 - RC : 10028', 0, 0, 'L');
        $pdf->Cell(120, 5, 'DATE : '. ($facture->getDate()->format('Y-m-d H:i')), 0, 1, 'R'); // Adjust the date format as needed

        $pdf->Ln(2);


        // Affichage des en-têtes du tableau
        $pdf->SetFillColor(204, 204, 204); // Couleur de fond du titre
        $pdf->SetTextColor(0, 0, 0); // Couleur du texte du titre
        foreach ($headers as $header) {
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(47.5, 10, utf8_decode($header), 0, 0, 'C', true); // true pour la couleur de fond
        }
        $pdf->Ln();

        // Affichage des données de la facture
        foreach ($data as $row) {
            foreach ($row as $key => $value) {
                $pdf->SetFont('Arial', '', 10.5);
                $pdf->Cell(47.5, 10, utf8_decode($value), 0, 0, 'C');
            }
            $pdf->Ln();
        }

        // Affichage du total de la facture
        $pdf->SetFont('Arial', 'B', 12);

        // Affichage du total de la facture
        $pdf->SetFillColor(204, 204, 204); // Couleur de fond du titre
        $pdf->SetTextColor(0, 0, 0); // Couleur du texte du titre
        $pdf->Cell(142.5, -10, 'Total', 0, 0, 'L', true); // true pour la couleur de fond
        $pdf->Cell(47.5, -10, utf8_decode($total . ' F'), 1, 1, 'C',true);

        // Téléchargement du fichier PDF
        $pdf->Output('D', $filename);
        exit;

    }

    #[Route('/chargement/statut/{id}', name: 'statut')]
    public function statut(Chargement $chargement, EntityManagerInterface $entityManager){

        $chargement->setStatut('payée');
        $nomChargement = $chargement->getNomClient();
        $totalCharge = $chargement->getTotal();
        $dettes = $entityManager->getRepository(Dette::class)->findAll();
        foreach ($dettes as $dette){
            $nomDette = $dette->getClient()->getNom();
            $montantDette = $dette->getReste();
            if ($nomChargement == $nomDette && $totalCharge == $montantDette){
                $dette->setStatut("payée");
                $entityManager->flush();
            }

        }
        $entityManager->persist($chargement);
        $entityManager->flush();
        $this->addFlash('success', 'Le paiement de la facture a été effectué.');

        return $this->redirectToRoute('liste_chargement');
    }

    #[Route('/chargement/retour/{id}', name: 'retour')]
    public function retour(Chargement $chargement)
    {
        $facture = new Facture();
        $factures = $chargement->addFacture($facture);
        foreach ($factures->getFacture() as $facture) {
            $f = $facture->getChargement()->getFacture()->toArray();
            array_pop($f);
            return $this->render('chargement/extraire.html.twig', ['f' => $f]);
        }
    }
    #[Route('/chargement/retour_produit/{id}', name: 'retour_produit')]
    public function retourProduit(Facture $facture, FactureRepository $repository, EntityManagerInterface $entityManager)
    {

        $produit = $facture->getProduit()->first();

        if ($produit){

            $p = $entityManager->getRepository(Produit::class)->find($produit);
            $vendus = $p->getNbreVendu();
            $nombre = $facture->getNombre();

            if ($facture->getNomProduit() == $p->getNomProduitDetail()){

                $repository->remove($facture);
                $quantite = floatval($facture->getQuantite());
                if ($quantite >= $nombre) {
                    $boxe = $quantite / $nombre;
                    $vendus = $boxe;
                    $dstock = $p->getQtStock() + $vendus;
                    $p->setQtStock($dstock);
                    $p->setNbreVendu($vendus);
                }else{
                    $boxe = $quantite / $nombre;
                    $vendus = $boxe;
                    $dstock = $p->getQtStock() + $vendus;
                    $p->setQtStock($dstock);
                    $p->setNbreVendu($vendus);
                }

                //Mise à jour du quantité Stock détail de la produit
                $upd = $p->getNombre() * $p->getQtStock();
                $p->setQtStockDetail($upd);

                //Mise à jour du total
                $upddd = $p->getQtStock() * $p->getPrixUnit();
                $p->setTotal($upddd);

                $this->addFlash('success', $produit->getNomProduitDetail().' a été annulé avec succès.');
                $entityManager->flush();
            } else
            {

                $repository->remove($facture); // Mise à jour de l'état de la facture

                //Mise à jour quantité stock produit et total produit
                $quantite = $facture->getQuantite();
                $p->setQtStock($p->getQtStock() + $quantite);
                $updProd = $p->getQtStock() * $p->getPrixUnit();
                if ($p->getNombre() != null){
                    $p->setQtStockDetail($p->getNombre() * $p->getQtStock());
                }
                $p->setTotal($updProd);
                $this->addFlash('success', $produit->getLibelle().' a été annulé avec succès.');
                $entityManager->flush();
            }

            return $this->redirectToRoute('liste_chargement');
        }
        $this->addFlash('error', 'Erreur lors de la suppression de la facture.');
        return $this->redirectToRoute('liste_chargement');
    }
}
