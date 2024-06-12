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
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChargementController extends AbstractController
{
    #[Route('/chargement', name: 'liste_chargement')]
    public function index(ChargementRepository $charge, Request $request, PaginatorInterface $paginator): Response
    {

        $search = new Search();
        $form2 = $this->createForm(SearchType::class, $search);
        $form2->handleRequest($request);
        $nom = $search->getNom();

        //$chargement = $nom ? $charge->findByName($nom) : $charge->findAllOrderedByDate();

        $pagination = $paginator->paginate(
            ($nom !== null && $nom !== '') ? $charge->findByName($nom) : $charge->findAllOrderedByDate(),
            $request->query->get('page', 1),
            10
        );
        $f = null;

        return $this->render('chargement/index.html.twig', [
            'controller_name' => 'ChargementController',
            'pagination' => $pagination,
            'f' => $f,
            'form2' => $form2->createView(),
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

        $this->addFlash('success', 'La facture a été supprimé avec succès');
        return $this->redirectToRoute('liste_chargement');
    }

    #[Route('/chargement/user/{id}', name: 'chargement_user')]
    public function user($id, EntityManagerInterface $entityManager)
    {
        $chargement = $entityManager->getRepository(Chargement::class)->find($id);
        if (!$chargement) {
            throw $this->createNotFoundException('Chargement non trouvé');
        }

        $user = $chargement->getConnect();

        // Assurez-vous de retourner un tableau avec les clés 'nom' et 'email' (ou autres informations nécessaires)
        return new JsonResponse([
            'user' => $user,
        ]);
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

            $reste = $chargement->getReste();
            $avance = $chargement->getAvance();
            $depot = $chargement->getDepot();

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

            $reste = $chargement->getReste();
            $avance = $chargement->getAvance();
            $depot = $chargement->getDepot();
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
        $filename = ($client !== null ? $client->getNom() : '') . date("Y-m-d_H-i", time()) . ".pdf";

        // Initialisation du PDF
        $pdf = new \FPDF();
        $pdf->AddPage();

        // Titre de la facture
        $pdf->SetFont('Arial', 'BI', 12); // Définir la police : Arial, style gras et italique (Bold Italic), taille 12
        $pdf->SetTextColor(0, 0, 0); // Définir la couleur du texte : noir (RVB : 0, 0, 0)
        $pdf->Cell(0, 10, $factures->getNumeroFacture(), 1, 1, 'C'); // Créer une cellule avec le numéro de la facture, avec une bordure
        $pdf->Ln(1); // Ajouter un saut de ligne (1 unité de hauteur)


        $prenomNom = $this->getUser() ? $this->getUser()->getPrenom() . ' ' . $this->getUser()->getNom() : 'Anonyme';
        $adresse = $this->getUser() ? $this->getUser()->getAdresse() : 'Anonyme';
        $phone = $this->getUser() ? $this->getUser()->getTelephone() : 'Anonyme';
        // Informations sur le commerçant et client
        $pdf->SetFont('Arial', 'I', 9);
        $pdf->SetTextColor(51, 51, 51); // Couleur du texte des informations
        $pdf->SetFillColor(204, 204, 204); // Couleur de fond du titre
        $pdf->Cell(70, 5, 'COMMERCANT : '.$prenomNom, 0, 0, 'L');
        $pdf->Cell(120, 5, 'CLIENT : ' . ($client ? $client->getNom() : ''), 0, 1, 'R');

        $pdf->Cell(70, 5, 'ADRESSE : Garage Nioro Kaolack - Senegal', 0, 0, 'L');
        $pdf->Cell(120, 5, 'ADRESSE : '. ($client ? $client->getAdresse() : ''), 0, 1, 'R');

        $pdf->Cell(70, 5, 'TELEPHONE : +221 77 570 65 97 - 77 578 00 49', 0, 0, 'L');
        $pdf->Cell(120, 5, 'TELEPHONE : '. ($client ? $client->getTelephone() : ''), 0, 1, 'R');

        $pdf->Cell(70, 5, 'NINEA : 0098379332M1 - RC : SN-KLK-2022 A 4546', 0, 0, 'L');

        $date = $facture->getDate();

        if ($date !== null) {
            $formattedDate = $date->format('Y-m-d H:i');
        } else {
            $formattedDate = 'Date non définie'; // ou une autre valeur par défaut appropriée
        }

        $pdf->Cell(120, 5, 'DATE : ' . $formattedDate, 0, 1, 'R');

        $pdf->Ln(2);


        // Affichage des en-têtes du tableau
        $pdf->SetTextColor(0, 0, 0); // Couleur du texte du titre
        $pdf->SetFont('Arial', 'B', 12);

// Créer une cellule fusionnée avec bordure et tabulations entre les en-têtes
        $tabSeparatedHeaders = implode("\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t", $headers); // Utilisez le caractère de tabulation "\t" entre les en-têtes
        $pdf->Cell(190, 10, utf8_decode($tabSeparatedHeaders), 1, 0, 'C', false); // Utilisez la chaîne $tabSeparatedHeaders
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
        $pdf->SetTextColor(0, 0, 0); // Couleur du texte du titre
        $pdf->Cell(190, 10, '', 1, 1, 'L', false); // Créer une cellule vide avec bordure

// Positionner "Total" à gauche
        $pdf->SetX(10);
        $pdf->SetY($pdf->GetY() - 10); // Remonter à la ligne précédente
        $pdf->Cell(142.5, 10, 'Total', 0, 0, 'L', false);

// Positionner le montant total à droite
        $pdf->SetX(140);
        $pdf->Cell(47.5, 10, utf8_decode($total . ' F'), 0, 1, 'R', false);



        // Affichage de l'avance si elle n'est pas nulle
        if ($avance !== null) {
            $pdf->Cell(14.5, 30, 'Avance:', 0, 0, 'L', false);
            $pdf->Cell(30.5, 30, utf8_decode($avance . ' '), 0, 1, 'C', false);
        }

        // Affichage du reste si il n'est pas nul
        if ($reste !== null) {
            $pdf->Cell(14.5, -15, 'Reste:', 0, 0, 'L', false);
            $pdf->Cell(30.5, -15, utf8_decode($reste . ' '), 0, 1, 'C', false);
        }

        if ($depot !== null) {
            $pdf->Cell(14.5, -15, 'Depot:', 0, 0, 'L', false);
            $pdf->Cell(30.5, -15, utf8_decode($depot . ' '), 0, 1, 'C', false);
        }


        // Forcer le téléchargement du fichier PDF
        $pdf->Output('D', $filename);

        exit;

    }

    #[Route('/chargement/remboursement/{id}', name: 'remboursement')]
    public function rembourserDettes(Request $request,Chargement $chargement, EntityManagerInterface $entityManager)
    {

        $nomClient = $chargement->getNomClient();
        // Trouver le client
        $client = $entityManager->getRepository(Client::class)->findOneBy(['nom' => $nomClient]); // Remplacez $nomClient par le nom du client concerné

        // Vérifier si le client a des dettes impayées
        $dettesImpayees = $entityManager->getRepository(Dette::class)->findBy([
            'client' => $client,
            'statut' => 'impayé'
        ]);
        if (!empty($dettesImpayees)) {
            // Mettre à jour le statut des dettes impayées et le montant restant
            foreach ($dettesImpayees as $dette) {

                if ($dette->getTag() !== '1' && $chargement->getStatut() !== 'payée'){

                    $nouveauTotal = $chargement->getTotal() + $dette->getMontantDette();
                    $chargement->setTotal($nouveauTotal);
                    $chargement->setStatut('ajoutée');
                    $dette->setMontantDette($nouveauTotal);
                    $dette->setReste($nouveauTotal);
                    $dette->setTag('1');
                    $entityManager->persist($dette);
                }else{
                    $this->addFlash('danger', 'Dette déjà ajouté au total précédemment.');
                    return $this->redirectToRoute('liste_chargement');
                }
            }

            // Enregistrer les modifications dans la base de données
            $entityManager->flush();

            // Ajouter un message de succès
            $this->addFlash('success', 'Les dettes impayées ont été remboursées avec succès.');
        } else {
            // Ajouter un message d'erreur si aucune dette impayée n'a été trouvée pour ce client
            $this->addFlash('danger', 'Le client n\'a aucune dette impayée à rembourser.');
        }

        // Rediriger l'utilisateur vers la page de liste des chargements ou toute autre page appropriée
        return $this->redirectToRoute('liste_chargement');
    }

    #[Route('/chargement/payer/{id}', name: 'payer')]
    public function payer(Request $request, Chargement $chargement, EntityManagerInterface $entityManager)
    {
        if ($chargement->getStatut() !== "payée") {
            // Mettre à jour le statut de la facture à "payée"
            $chargement->setStatut('payée');

            // Rechercher le client associé à la facture
            $nomClient = $chargement->getNomClient();
            $client = $entityManager->getRepository(Client::class)->findOneBy(['nom' => $nomClient]);

            if ($client) {
                // Rechercher les dettes impayées du client
                $dettes = $entityManager->getRepository(Dette::class)->findBy(['client' => $client, 'statut' => 'impayé']);

                if (!empty($dettes)) {
                    // Mettre à jour la première dette trouvée à "payée" et définir le reste à 0
                    $dette = $dettes[0];
                    $dette->setStatut('payée');
                    $dette->setReste('0');
                    $entityManager->persist($dette);
                }
            }

            // Enregistrer les modifications dans la base de données
            $entityManager->flush();

            $this->addFlash('success', 'La facture a été payée avec succès.');
        } else {
            $this->addFlash('danger', 'Facture déjà payée.');
        }

        return $this->redirectToRoute('liste_chargement');
    }



    #[Route('/chargement/statut/{id}', name: 'statut')]
    public function statut(Request $request, Chargement $chargement, EntityManagerInterface $entityManager){

        $prixAvance = $request->request->get('price');

        $nomClient = $chargement->getNomClient();
        $client = $entityManager->getRepository(Client::class)->findOneBy(['nom' => $nomClient]);

        if (!$prixAvance) {
            $this->addFlash('error', 'Le prix doit être renseigné.');
            return $this->redirectToRoute('liste_chargement');
        }

        $reste = $chargement->getTotal() - $prixAvance;

        if ($reste == 0){
            $chargement->setStatut('payée');
            if ($client) {
                $dettes = $entityManager->getRepository(Dette::class)->findBy(['client' => $client, 'statut' => 'impayé']);
                foreach ($dettes as $d) {
                    $d->setStatut('payée');
                    $d->setReste('0');
                }
            }

            $entityManager->persist($chargement);
            $this->addFlash('success', 'Le règlement de la facture a été effectué.');
        } elseif ($chargement->getAvance() != null){
            $this->addFlash('danger', 'Vous avez déjà effectué un acompte auparavant.');
        } elseif ($reste > 0 && $reste < $chargement->getTotal()) {
            $dette = new Dette();
            $date = new \DateTime();
            $dette->setMontantDette($reste);
            $dette->setReste($reste);
            $dette->setDateCreated($date);
            $dette->setStatut('impayé');

            $chargement->setReste($reste);
            $chargement->setAvance($prixAvance);
            $chargement->setStatut('avance');


            if ($client) {
                $dettes = $entityManager->getRepository(Dette::class)->findBy(['client' => $client, 'statut' => 'impayé']);

                if (!empty($dettes)) {
                    $this->addFlash('danger', $client->getNom() . ' a déjà une dette impayée.');
                    return $this->redirectToRoute('liste_chargement');
                } else {
                    $dette->setClient($client);
                    $entityManager->persist($dette);
                    $this->addFlash('success', 'Le paiement de la facture a été effectué.');
                }
            } else {
                $this->addFlash('danger', 'Client non trouvé.');
            }
        }elseif($reste < 0){

            $chargement->setDepot(abs($reste));
            $chargement->setAvance($prixAvance);
            $chargement->setStatut('payée');

            $this->addFlash('success', 'Le client à un dépot de '.abs($reste));
        }

        $entityManager->flush();
        return $this->redirectToRoute('liste_chargement');
    }

    #[Route('/chargement/retour/{id}', name: 'retour')]
    public function retour(Chargement $chargement, EntityManagerInterface $entityManager)
    {
        $verifierFacture1 = $entityManager->getRepository(Facture::class)->findBy(['etat' => 1]);
        $verifierFacture2 = $entityManager->getRepository(Facture2::class)->findBy(['etat' => 1]);

        if (empty($verifierFacture1)) {
            $nomProduit = $chargement->getFacture()->toArray();

            foreach ($nomProduit as $fac) {
                $facture = new Facture();

                $nomProd = $fac->getNomProduit();
                $qte = $fac->getQuantite();
                $montant = $fac->getMontant();
                $prixUnit = $fac->getPrixUnit();
                $connect = $fac->getConnect();
                $client = $fac->getClient();
                $nomClient = $fac->getNomClient();

                $facture->setNomProduit($nomProd);
                $facture->setQuantite($qte);
                $facture->setPrixUnit($prixUnit);
                $facture->setMontant($montant);
                $facture->setConnect($connect);
                $facture->setClient($client);
                $facture->setNomClient($nomClient);

                $entityManager->persist($facture);
            }
            $entityManager->flush();

            return $this->redirectToRoute('facture_liste');

        } elseif (empty($verifierFacture2)) {
            $nomProduit = $chargement->getFacture2s()->toArray();

            foreach ($nomProduit as $fac) {
                $facture = new Facture2();

                $nomProd = $fac->getNomProduit();
                $qte = $fac->getQuantite();
                $montant = $fac->getMontant();
                $prixUnit = $fac->getPrixUnit();
                $connect = $fac->getConnect();
                $client = $fac->getClient();
                $nomClient = $fac->getNomClient();

                $facture->setNomProduit($nomProd);
                $facture->setQuantite($qte);
                $facture->setPrixUnit($prixUnit);
                $facture->setMontant($montant);
                $facture->setConnect($connect);
                $facture->setClient($client);
                $facture->setNomClient($nomClient);

                $entityManager->persist($facture);
                
            }

            // Flush une seule fois après la boucle
            $entityManager->flush();

            return $this->redirectToRoute('facture2_liste');

        } else {
            $this->addFlash('danger', 'Facture1 et Facture2 sont occupées.');
            return $this->redirectToRoute('liste_chargement');
        }

       
    }


    #[Route('/chargement/retour_produit/{id}', name: 'retour_produit')]
    public function retourProduit(Facture $facture, FactureRepository $repository, EntityManagerInterface $entityManager)
    {

        $produit = $facture->getProduit()->first();

        if ($produit){

            $p = $entityManager->getRepository(Produit::class)->find($produit);

                $repository->remove($facture); // Mise à jour de l'état de la facture

                //Mise à jour quantité stock produit et total produit
                $quantite = $facture->getQuantite();
                $p->setQtStock($p->getQtStock() + $quantite);
                $updProd = $p->getQtStock() * $p->getPrixUnit();

                $p->setTotal($updProd);
                $this->addFlash('success', $produit->getLibelle().' a été annulé avec succès.');
                $entityManager->flush();


            return $this->redirectToRoute('liste_chargement');
        }
        $this->addFlash('error', 'Erreur lors de la suppression de la facture.');
        return $this->redirectToRoute('liste_chargement');
    }
}
