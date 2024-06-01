<?php

namespace App\Controller;

use App\Entity\Chargement;
use App\Entity\Client;
use App\Entity\Dette;
use App\Entity\Facture;
use App\Entity\Produit;
use App\Entity\Search;
use App\Repository\ClientRepository;
use App\Repository\FactureRepository;
use App\Repository\ProduitRepository;
use App\Service\FactureService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class FactureController extends AbstractController
{

    private $enregistrerClicked = false;
    #[Route('/facture', name: 'facture_liste')]
    public function index(
        FactureRepository $fac,
        ProduitRepository $prod,
        ClientRepository $clientRepository,
    ): Response
    {

        // Récupération de toutes les factures
        $factures = $fac->findAllOrderedByDate();

        $produits = $prod->findAllOrderedByDate();
        $clients = $clientRepository->findAllOrderedByDate();

        return $this->render('facture/index.html.twig', [
            'produits' => $produits,
            'facture' => $factures,
            'clients' => $clients,
        ]);
    }

    #[Route('/produit/modifier/{id}', name: 'modifier')]
    public function modifier($id, FactureRepository $repo, Request $request, EntityManagerInterface $entityManager): Response
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
                return $this->redirectToRoute('facture_liste');
            }

            // Assurez-vous que la quantité en stock ne devient pas négative
            $produit->setQtStock(max(0, $nouvelleQuantiteStock));
            $produit->setTotal($produit->getQtStock()* $produit->getPrixUnit());

            $total = $this->factureService->updateTotalForFactures();
            $facture->setTotal($total);
            // Enregistrez les modifications
            $entityManager->flush();

            return $this->redirectToRoute('facture_liste');
        }

        // Récupérer la liste des produits pour afficher dans le formulaire
        $produits = $entityManager->getRepository(Produit::class)->findAll();
        return $this->render('facture/editer.html.twig', [
            'facture' => $facture,
            'produits' => $produits,
        ]);
    }

    #[Route('/facture/delete/{id}', name: 'facture_delete')]
    public function delete(Facture $facture,EntityManagerInterface $entityManager, FactureRepository $repository)
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
                        $this->addFlash('success', $produit->getLibelle().' a ete supprimée avec succès.');
                        $entityManager->flush();
                    

                return $this->redirectToRoute('facture_liste');
            }
        $this->addFlash('error', 'Erreur lors de la suppression de la facture.');
        return $this->redirectToRoute('facture_liste');
    }

    #[Route('/facture/save/all', name: 'save')]
    public function delete_all(EntityManagerInterface $entityManager)
    {

            $repository = $entityManager->getRepository(Facture::class);
            $factures = $repository->findBy(['etat' => 1], ['date' => 'DESC']);

            $client = null;
            $adresse = null;
            $telephone = null;
            $nom = null;
            $impayé = null;

            if (!empty($factures)) {
                $firstFacture= end($factures);
                if ($firstFacture->getClient() !== null) {
                    $nom = $firstFacture->getNomClient();
                    $adresse = $firstFacture->getClient()->getAdresse();
                    $telephone = $firstFacture->getClient()->getTelephone();
                }
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
                $chargement->addFacture($facture);
                $entityManager->persist($facture);
            }

            $chargement->setConnect($facture->getConnect());
            $chargement->setNumeroFacture('FACTURE-' . $facture->getId() );
            $chargement->setStatut('En cours');
            $chargement->setTotal($total);

            $entityManager->persist($chargement);
            $entityManager->flush();

        return $this->redirectToRoute('liste_chargement');

    }

    private $factureService;

    public function __construct(FactureService $factureService, Security $security)
    {
        $this->factureService = $factureService;
        $this->security = $security;
    }

    #[Route('/facture/rajout/{id}', name: 'rajout_facture')]
    public function add($id, EntityManagerInterface $entityManager, Request $request, Security $security): RedirectResponse
    {

        $user = $security->getUser();
        if (!$user) {
            $this->addFlash('danger', 'Vous devez être connecté pour ajouter une facture.');
            return $this->redirectToRoute('login'); // Adjust the route to your login page
        }

        $actionType = $request->query->get('actionType', 'addToFacture');
        $quantity = $request->query->get('quantity', 1);
        $clientId = $request->query->get('clientId');
        $user = $this->getUser();

        try {
            $facture = $this->factureService->createFacture($id, $quantity, $clientId, $user, $actionType);
            $total = $this->factureService->updateTotalForFactures();

            return $this->redirectToRoute('facture_liste', ['total' => $total]);
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('facture_liste');
        }
    }


    #[Route('/search', name: 'search')]
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
                'path' => $this->generateUrl('rajout_facture', ['id' => $produit->getId()]),
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


}
