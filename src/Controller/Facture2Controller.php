<?php

namespace App\Controller;

use App\Entity\Chargement;
use App\Entity\Client;
use App\Entity\Dette;
use App\Entity\Facture2;
use App\Entity\Produit;
use App\Entity\Search;
use App\Repository\ClientRepository;
use App\Repository\Facture2Repository;
use App\Repository\ProduitRepository;
use App\Service\Facture2Service;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class Facture2Controller extends AbstractController
{
    private $enregistrerClicked = false;
    #[Route('/facture2', name: 'facture2_liste')]
    public function index(
        Facture2Repository $fac,
        ProduitRepository $prod,
        ClientRepository $clientRepository,
    ): Response
    {

        // Récupération de toutes les factures
        $factures = $fac->findAllOrderedByDate();

        $produits = $prod->findAllOrderedByDate();
        $clients = $clientRepository->findAllOrderedByDate();

        return $this->render('facture2/index.html.twig', [
            'produits' => $produits,
            'facture' => $factures,
            'clients' => $clients,
        ]);
    }

    private $factureService;

    public function __construct(Facture2Service $factureService, Security $security)
    {
        $this->factureService = $factureService;
        $this->security = $security;
    }

    #[Route('/facture2/add/{id}', name: 'facture2_add')]
    public function add($id, EntityManagerInterface $entityManager, Request $request, Security $security): RedirectResponse
    {

        $user = $security->getUser();
        if (!$user) {
            $this->addFlash('danger', 'Vous devez être connecté pour ajouter une facture.');
            return $this->redirectToRoute('login'); // Adjust the route to your login page
        }

        $quantity = $request->query->get('quantity', 1);
        $clientId = $request->query->get('clientId');
        $user = $this->getUser();

        try {
            $facture = $this->factureService->createFacture2($id, $quantity, $clientId, $user);
            $total = $this->factureService->updateTotalForFactures();

            return $this->redirectToRoute('facture2_liste', ['total' => $total]);
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('facture2_liste');
        }
    }

    #[Route('/search2', name: 'search2')]
    public function search(Request $request, ProduitRepository $prod, Security $security): JsonResponse
    {
        $user = $security->getUser();
        if (!$user) {
            $this->addFlash('warning', 'Vous devez être connecté pour ajouter une facture.');
            return $this->redirectToRoute('app_login');
        }
        $searchTerm = $request->query->get('term');
        $produits = $prod->findByName($searchTerm);

        $data = [];
        foreach ($produits as $produit) {
            $data[] = [
                'id' => $produit->getId(),
                'libelle' => $produit->getLibelle(),
                'path' => $this->generateUrl('facture2_add', ['id' => $produit->getId()]),
            ];
        }

        return $this->json($data);
    }

    #[Route('/search-clients', name: 'search_clients')]
    public function searchClients(Request $request, ClientRepository $clientRepository): JsonResponse
    {
        $searchTerm = $request->query->get('term');
        $clients = $clientRepository->findByNameClient($searchTerm);

        $data = [];
        foreach ($clients as $client) {
            $data[] = [
                'id' => $client->getId(),
                'nom' => $client->getNom(),
            ];
        }

        return $this->json($data);
    }

    #[Route('/produit/modifier2/{id}', name: 'modifier2')]
    public function modifier($id, Facture2Repository $repo, Request $request, EntityManagerInterface $entityManager): Response
    {
        $facture = $repo->find($id);
        if (!$facture) {
            throw $this->createNotFoundException('Facture non trouvée');
        }

        if ($request->isMethod('POST')) {
            // Récupérer les données modifiées depuis la requête
            $quantiteInitiale = $facture->getQuantite(); // Ancienne quantité
            $quantiteNouvelle = $request->request->get('quantite');
            // Calculer la différence de quantité
            $differenceQuantite = $quantiteNouvelle - $quantiteInitiale;

            $prixUnit = $request->request->get('prixUnit');
            $produitId = $request->request->get('produit');

            $produit = $entityManager->getRepository(Produit::class)->find($produitId);

            // Mettre à jour la facture avec les nouvelles données
            $facture->setQuantite($quantiteNouvelle);
            $facture->setNomProduit($produit);
            $facture->setPrixUnit($prixUnit);
            $facture->setMontant($quantiteNouvelle * $prixUnit);

            // Mettre à jour la quantité en stock du produit
            $quantiteStockActuelle = $produit->getQtStock();

            if ($differenceQuantite > 0) {
                // Nouvelle quantité est supérieure à l'ancienne
                $nouvelleQuantiteStock = $quantiteStockActuelle - $differenceQuantite;
            } elseif ($differenceQuantite < 0) {
                // Nouvelle quantité est inférieure à l'ancienne
                $nouvelleQuantiteStock = $quantiteStockActuelle + abs($differenceQuantite);
            } elseif ($differenceQuantite == 0) {

                $total = $this->factureService->updateTotalForFactures();
                $facture->setTotal($total);
                // Nouvelle quantité est égale à l'ancienne
                $entityManager->flush();
                return $this->redirectToRoute('facture2_liste');
            }

            // Assurez-vous que la quantité en stock ne devient pas négative
            $produit->setQtStock(max(0, $nouvelleQuantiteStock));
            $produit->setTotal($produit->getQtStock()* $produit->getPrixUnit());

            $total = $this->factureService->updateTotalForFactures();
            $facture->setTotal($total);
            // Enregistrez les modifications
            $entityManager->flush();

            return $this->redirectToRoute('facture2_liste');
        }

        // Récupérer la liste des produits pour afficher dans le formulaire
        $produits = $entityManager->getRepository(Produit::class)->findAll();

        return $this->render('facture2/editer.html.twig', [
            'facture' => $facture,
            'produits' => $produits,
        ]);
    }

    #[Route('/facture2/delete/{id}', name: 'facture2_delete')]
    public function delete(Facture2 $facture,EntityManagerInterface $entityManager, Facture2Repository $repository)
    {
        $produit = $facture->getProduit()->first();

        if ($produit !== false) {

            $p = $entityManager->getRepository(Produit::class)->find($produit);
            $repository->remove($facture); // Mise à jour de l'état de la facture

            //Mise à jour quantité stock produit et total produit
            $quantite = $facture->getQuantite();
            $p->setQtStock($p->getQtStock() + $quantite);
            $updProd = $p->getQtStock() * $p->getPrixUnit();
            $p->setTotal($updProd);
            $this->addFlash('success', $produit->getLibelle() . ' a ete supprimée avec succès.');
            $entityManager->flush();


            return $this->redirectToRoute('facture2_liste');
        } else {
            $produit = $facture->getProduit()->getOwner()->getNomProduit();
            $p = $entityManager->getRepository(Produit::class)->findBy(['libelle' => $produit]);

            $repository->remove($facture); // Mise à jour de l'état de la facture

            //Mise à jour quantité stock produit et total produit
            $quantite = $facture->getQuantite();
            $p[0]->setQtStock($p[0]->getQtStock() + $quantite);
            $updProd = $p[0]->getQtStock() * $p[0]->getPrixUnit();
            $p[0]->setTotal($updProd);
            $this->addFlash('success', 'Produit supprimé avec succès.');
            $entityManager->flush();


            return $this->redirectToRoute('facture2_liste');
        }
        $this->addFlash('error', 'Erreur lors de la suppression de la facture.');
        return $this->redirectToRoute('facture2_liste');
    }

    #[Route('/facture/delete_all', name: 'facture2_delete_all')]
    public function deleteAll(EntityManagerInterface $entityManager)
    {
        $repository = $entityManager->getRepository(Facture2::class);
        $factures = $repository->findBy(['etat' => 1], ['date' => 'DESC']);

        $client = null;
        $adresse = null;
        $telephone = null;
        $nom = null;
        $impayé = null;

        if (!empty($factures)) {
            $firstFacture = end($factures);
            $endFacture = reset($factures);
            if ($firstFacture->getClient() !== null) {
                $nom = $firstFacture->getNomClient();
                $adresse = $firstFacture->getClient()->getAdresse();
                $telephone = $firstFacture->getClient()->getTelephone();
            } elseif ($endFacture->getClient() !== null) {
                $nom = $endFacture->getNomClient();
                $adresse = $endFacture->getClient()->getAdresse();
                $telephone = $endFacture->getClient()->getTelephone();
            } else {
                $lastFacture = end($factures); // Récupère la dernière facture dans le tableau
                
                if ($lastFacture instanceof Facture2) { // Vérifie que c'est bien une instance de Facture
                    $nom = $lastFacture->getChargement()->getNomClient();
                    $adresse = $lastFacture->getChargement()->getAdresse();
                    $telephone = $lastFacture->getChargement()->getTelephone();
                }
            }
        } else {
            $this->addFlash('danger', 'Aucune facture trouvée.');
            return;
        }

        if ($nom) {
            $dettesImpayees = $entityManager->getRepository(Dette::class)->findBy([
                'statut' => 'impayé',
                'client' => $entityManager->getRepository(Client::class)->findOneBy(['nom' => $nom])
            ]);
            if (!empty($dettesImpayees)) {
                $impayé = $dettesImpayees[0]->getReste();
            }
        }

        // Save invoices to the Chargement table
        $chargement = new Chargement();
        $chargement->setNomClient($nom);
        $chargement->setAdresse($adresse);
        $chargement->setTelephone($telephone);
        $chargement->setNombre(count($factures));
        $chargement->setDetteImpaye($impayé);
        if ($chargement->getNombre() == 0) {
            return $this->redirectToRoute('facture_liste');
        }
        $date = new \DateTime();
        $chargement->setDate($date);
        $total = 0;
        foreach ($factures as $facture) {
            $total = $facture->getTotal();

            $facture->setEtat(0);
            $facture->setChargement($chargement);
            $chargement->addFacture2($facture);
            $entityManager->persist($facture);
        }

        $chargement->setConnect($facture->getConnect());
        $chargement->setNumeroFacture('FACTURE-' . $facture->getId());
        $chargement->setStatut('En cours');

        if ($total == null) {
            foreach ($factures as $montantTotal) {
                $total += $montantTotal->getMontant();
            }
        }

        $chargement->setTotal($total);

        $entityManager->persist($chargement);
        $entityManager->flush();

        return $this->redirectToRoute('liste_chargement');
    }


}
