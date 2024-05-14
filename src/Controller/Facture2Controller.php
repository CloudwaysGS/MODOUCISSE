<?php

namespace App\Controller;

use App\Entity\Chargement;
use App\Entity\Client;
use App\Entity\Dette;
use App\Entity\Facture2;
use App\Entity\Produit;
use App\Entity\Search;
use App\Form\Facture2Type;
use App\Repository\ClientRepository;
use App\Repository\Facture2Repository;
use App\Repository\FactureRepository;
use App\Repository\ProduitRepository;
use App\Service\Facture2Service;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
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
        PaginatorInterface $paginator,
    ): Response
    {

        // Récupération de toutes les factures
        $factures = $fac->findAllOrderedByDate();

        $search = new Search();
        $nom = $search->getNom();

        $produits = $nom ? $prod->findByName($nom) : $prod->findAllOrderedByDate();
        $details = $prod->findAllDetail();
        $clients = $clientRepository->findAll();

        return $this->render('facture2/index.html.twig', [
            'produits' => $produits,
            'details' => $details,
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
            $this->addFlash('warning', 'Vous devez être connecté pour ajouter une facture.');
            return $this->redirectToRoute('login'); // Adjust the route to your login page
        }
        $quantityDetail = null;
        $clientIdDetail = null;
        $actionType = $request->query->get('actionType', 'addToFacture');

        if ($actionType == 'addToFactureDetail'){
            $quantityDetail = $request->query->get('quantityDetail', 1);
            $clientIdDetail = $request->query->get('clientIdDetail');
        }

        $quantity = $request->query->get('quantity', 1);
        $clientId = $request->query->get('clientId');

        try {
            $facture = $this->factureService->createFacture2($id, $quantity, $clientId, $user, $actionType, $quantityDetail, $clientIdDetail );
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

    #[Route('/searchDetail2', name: 'searchDetail2')]
    public function searchDetail(Request $request, ProduitRepository $prod, Security $security): JsonResponse
    {
        $user = $security->getUser();
        if (!$user) {
            $this->addFlash('warning', 'Vous devez être connecté pour ajouter une facture.');
            return $this->redirectToRoute('app_login');
        }
        $searchTerm = $request->query->get('term');
        $produits = $prod->findByNameDetail($searchTerm);
        $data = [];
        foreach ($produits as $produit) {
            $data[] = [
                'id' => $produit->getId(),
                'nomProduitDetail' => $produit->getNomProduitDetail(),
                'path' => $this->generateUrl('facture2_add', ['id' => $produit->getId()]),
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

            if ($facture->getNomProduit() == $produit->getNomProduitDetail()){
                // Mettre à jour la facture avec les nouvelles données
                $facture->setQuantite($quantiteNouvelle);
                $facture->setNomProduit($produit->getNomProduitDetail());
                $facture->setPrixUnit($prixUnit);
                $facture->setMontant($quantiteNouvelle * $prixUnit);
                // Mettre à jour la quantité en stock du produit
                $quantiteStockActuelle = $produit->getQtStockDetail();
                $nombre = $produit->getNombre();
                if ($differenceQuantite > 0) {

                    // Nouvelle quantité est supérieure à l'ancienne
                    $nouvelleQuantiteStockDetail = $quantiteStockActuelle - $differenceQuantite;
                    $nouvelleQuantiteStock = $nouvelleQuantiteStockDetail / $nombre;
                    $nouvelleNombreVendu = $differenceQuantite / $nombre;

                } elseif ($differenceQuantite < 0) {
                    // Nouvelle quantité est inférieure à l'ancienne
                    $nouvelleQuantiteStockDetail = $quantiteStockActuelle + abs($differenceQuantite);
                    $nouvelleQuantiteStock = $nouvelleQuantiteStockDetail / $nombre;
                    $nouvelleNombreVendu = abs($differenceQuantite) / $nombre;

                } elseif ($differenceQuantite == 0) {
                    // Nouvelle quantité est égale à l'ancienne
                    $entityManager->flush();
                    return $this->redirectToRoute('facture2_liste');
                }
                // Assurez-vous que la quantité en stock ne devient pas négative
                $produit->setQtStockDetail(max(0, $nouvelleQuantiteStockDetail));
                $produit->setQtStock($nouvelleQuantiteStock);
                $produit->setNbreVendu($nouvelleNombreVendu);
                $produit->setTotal($produit->getQtStock()* $produit->getPrixUnit());

                $total = $this->factureService->updateTotalForFactures();
                $facture->setTotal($total);
                // Enregistrez les modifications
                $entityManager->flush();

                return $this->redirectToRoute('facture2_liste');
            }

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
                // Nouvelle quantité est égale à l'ancienne
                $entityManager->flush();
                return $this->redirectToRoute('facture2_liste');
            }

            // Assurez-vous que la quantité en stock ne devient pas négative
            $produit->setQtStock(max(0, $nouvelleQuantiteStock));
            $produit->setTotal($produit->getQtStock()* $produit->getPrixUnit());
            if ($produit->getNombre() != null){
                $produit->setQtStockDetail($produit->getNombre() * $produit->getQtStock());
            }
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
        if ($produit){
            $p = $entityManager->getRepository(Produit::class)->find($produit);
            $vendu = $p->getNbreVendu();
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

                $this->addFlash('success', $produit->getNomProduitDetail().' a ete supprimée avec succès.');
            } else {
                $repository->remove($facture); // Mise à jour de l'état de la facture

                //Mise à jour quantité stock produit et total produit
                $quantite = $facture->getQuantite();
                $p->setQtStock($p->getQtStock() + $quantite);
                $updProd = $p->getQtStock() * $p->getPrixUnit();
                $p->setTotal($updProd);
                $this->addFlash('success', $produit->getLibelle().' a ete supprimée avec succès.');
            }

            $entityManager->flush();

            return $this->redirectToRoute('facture2_liste');
        }
        $this->addFlash('error', 'Erreur lors de la suppression de la facture.');
        return $this->redirectToRoute('facture2_liste');
    }

    #[Route('/facture/delete_all', name: 'facture2_delete_all')]
    public function deleteAll(EntityManagerInterface $entityManager)
    {
        if (!$this->enregistrerClicked) {
            $repository = $entityManager->getRepository(Facture2::class);
            $factures = $repository->findBy(['etat' => 1], ['date' => 'DESC']);

            $client = null;
            $adresse = null;
            $telephone = null;
            if (!empty($factures)) {
                $firstFacture= end($factures);
                if ($firstFacture->getClient() !== null) {
                    $nom = $firstFacture->getClient()->getNom();
                    $adresse = $firstFacture->getClient()->getAdresse();
                    $telephone = $firstFacture->getClient()->getTelephone();
                }
            }
            // Save invoices to the Chargement table
            $chargement = new Chargement();
            $chargement->setNomClient($nom);
            $chargement->setAdresse($adresse);
            $chargement->setTelephone($telephone);
            $chargement->setNombre(count($factures));
            if ($chargement->getNombre() == 0) {
                return $this->redirectToRoute('facture2_liste');
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
            $chargement->setNumeroFacture('FACTURE2-' . $facture->getId() );
            $chargement->setStatut('En cours');
            $chargement->setTotal($total);
            $entityManager->persist($chargement);
            $entityManager->flush();

            $dette = new Dette();
            $date = new \DateTime();
            $dette->setMontantDette($chargement->getTotal());
            $dette->setReste($chargement->getTotal());
            $dette->setDateCreated($date);
            $dette->setStatut('impayé');
            $nomClient = $chargement->getNomClient();
            $client = $entityManager->getRepository(Client::class)->findOneBy(['nom' => $nomClient]);
            $dette->setClient($client);
            $dette->setCommentaire('Dette de la facture');
            $dettes = $entityManager->getRepository(Dette::class)->findAll();
            foreach ( $dettes as $s) {
                if ( $dette->getClient()->getNom() === $s->getClient()->getNom() && $s->getStatut() == "impayé" && $s->getReste() != 0) {
                    $chargement->setStatut('impayé');
                    $entityManager->flush();
                    $this->addFlash('danger',$s->getClient()->getNom().' a déjà une dette non payée.');
                    return $this->redirectToRoute('liste_chargement');
                }
            }

            $entityManager->persist($dette);
            $entityManager->flush();
            return $this->redirectToRoute('facture2_liste');
        }
    }


}
