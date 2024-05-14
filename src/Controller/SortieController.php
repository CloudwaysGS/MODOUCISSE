<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Produit;
use App\Entity\Sortie;
use App\Repository\ProduitRepository;
use App\Repository\SortieRepository;
use App\Service\SortieValidatorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;


class SortieController extends AbstractController
{
    #[Route('/sortie/liste', name: 'sortie_liste')]
    public function index(SortieRepository $sort, ProduitRepository $prod, PaginatorInterface $paginator, Request $request): Response
    {
        $produits = $prod->findAll();
        $pagination = $paginator->paginate(
            $sort->findAllOrderedByDate(), // Corrected variable name and method call
            $request->query->get('page', 1),
            10
        );

        return $this->render('sortie/liste.html.twig', [
            'controller_name' => 'SortieController',
            'pagination'=> $pagination,
            'produits' => $produits,
            
        ]);

        return $this->render('sortie/liste.html.twig');
    }

    #[Route('/sortie/add', name: 'sortie_add')]
    public function add(EntityManagerInterface $manager, Request $request, SortieValidatorService $validatorService): Response
    {
        if ($request->isMethod('POST')) {
            // Get the data from the request
            $produitId = $request->request->get('produit_id');
            $qtSortie = $request->request->get('qt_sortie');
            $prixUnit = $request->request->get('prix_unit');
            
            $validationErrors = $validatorService->validate([
                'produitId' => $produitId,
                'qtSortie' => $qtSortie,
                'prixUnit' => $prixUnit,
            ]);

            if (!empty($validationErrors)) {
                foreach ($validationErrors as $error) {
                    $this->addFlash('danger', $error);
                }

                return $this->redirectToRoute('sortie_liste');
            }
            
            if (!empty($produitId)){
                $sortie = new Sortie();
                $date = new \DateTime();
                $sortie->setDateSortie($date);
                $sortie->setQtSortie($qtSortie);
                $sortie->setPrixUnit($prixUnit);

                $produit = $manager->getRepository(Produit::class)->find($produitId);
                if (!$produit) {
                    $this->addFlash('danger', 'Produit not found.');
                    return $this->redirectToRoute('sortie_liste');
                }

                $qtStock = $produit->getQtStock();
                if ($qtStock < $qtSortie) {
                    $this->addFlash('danger', 'La quantité en stock est insuffisante pour satisfaire la demande. Quantité stock : ' . $qtStock);
                } else
                {

                    $sortie->setProduit($produit);
                    $sortie->setTotal($prixUnit * $qtSortie);
                    $user = $this->getUser();
                    $sortie->setUser($user);

                    $manager->persist($sortie);
                    $manager->flush();
                    // Mise à jour qtestock produit
                    $produit->setQtStock($qtStock - $qtSortie);
                    $produit->setTotal($produit->getPrixUnit() * $produit->getQtStock());

                    $manager->persist($produit);
                    $manager->flush();

                    $this->addFlash('success', 'Le produit a été enregistré avec succès.');
                }
            }

        }

        return $this->redirectToRoute('sortie_liste');
    }

    #[Route('/sortie/modifier/{id}', name: 'sortie_modifier')]
    public function modifier(EntityManagerInterface $manager, Request $request, SortieRepository $sortieRepository,ProduitRepository $detail, int $id): Response
    {
        $sortie = $sortieRepository->find($id);
        if ($request->isMethod('POST')){

            $qtSortie = $request->request->get('qt_sortie');
            $prixUnit = $request->request->get('prix_unit');

            $sortie->setQtSortie($qtSortie);
            $sortie->setPrixUnit($prixUnit);
            $total = $sortie->getQtSortie() *$sortie->getPrixUnit();
            $sortie->setTotal($total);
            $manager->flush();
            $this->addFlash('success', 'La sortie a été modifiée avec succès.');
            return $this->redirectToRoute('sortie_liste');
        }

        $clients = $manager->getRepository(Client::class)->findAll();
        $produits = $manager->getRepository(Produit::class)->findAll();
        $details = $detail->findAllDetail();

        return $this->render('sortie/editer.html.twig', [
            'sortie' => $sortie,
            'clients' => $clients,
            'produits' => $produits,
            'details' => $details,

        ]);
    }

    #[Route('/sortie/delete/{id}', name: 'sortie_delete')]
    public function delete(Sortie $sortie, SortieRepository $repository, EntityManagerInterface $manager){
        $repository->remove($sortie,true);
        $p = $manager->getRepository(Produit::class)->find($sortie->getProduit()->getId());
         {
            $stock = $p->getQtStock() + $sortie->getQtSortie();
            $upd = $stock * $p->getPrixUnit();
            $p->setQtStock($stock);
            $p->setTotal($upd);

            $manager->flush();
        }

        $this->addFlash('success', 'Le produit sorti a été supprimé avec succès');
        return $this->redirectToRoute('sortie_liste');
    }

}
