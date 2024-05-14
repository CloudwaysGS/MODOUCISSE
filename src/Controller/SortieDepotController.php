<?php

namespace App\Controller;

use App\Entity\Depot;
use App\Entity\Produit;
use App\Entity\SortieDepot;
use App\Form\SortieDepotType;
use App\Repository\DepotRepository;
use App\Repository\ProduitRepository;
use App\Repository\SortieDepotRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/sortie/depot')]
class SortieDepotController extends AbstractController
{
    #[Route('/', name: 'app_sortie_depot_index', methods: ['GET'])]
    public function index(SortieDepotRepository $sortieDepotRepository, PaginatorInterface $paginator, Request $request): Response
    {

        $pagination = $paginator->paginate(
            $sortieDepotRepository->findAllOrderedByDate(),
            $request->query->get('page', 1),
            10
        );
        return $this->render('sortie_depot/index.html.twig', [
            'pagination' => $pagination,
            ]);
    }

    #[Route('/new', name: 'app_sortie_depot_new', methods: ['GET', 'POST'])]
    public function new(Request $request, SortieDepotRepository $sortieDepotRepository, EntityManagerInterface $manager, DepotRepository $depotRepository, ProduitRepository $produitRepository): Response
    {
        $sortieDepot = new SortieDepot();
        $form = $this->createForm(SortieDepotType::class, $sortieDepot);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $depot = $sortieDepot->getDepot();
            $produit = $sortieDepot->getProduit();
            if (!$depot) {
                $this->addFlash('danger', 'Veuillez sélectionner un produit.');
                return $this->redirectToRoute('app_sortie_depot_new');
            }

            $d = $manager->getRepository(Depot::class)->find($depot->getId());
            //$p = $manager->getRepository(Produit::class)->find($produit->getId());
            if ($sortieDepot->getQtSortie() > $d->getStock()) {
                $this->addFlash('danger', 'La quantité en stock est insuffisante pour satisfaire la demande. Quantité stock : ' . $d->getStock());
                return $this->redirectToRoute('app_sortie_depot_new');
            }
            $date = new \DateTime();
            $sortieDepot->setReleaseDate($date);
            $nomProduit = $depot->getLibelle();
            $sortieDepot->setLibelle($nomProduit);

            //***Mise à jour***//
            $p = $produitRepository->findAll();
            foreach ($p as $produit){
                if ($produit->getLibelle() == $sortieDepot->getLibelle()){
                    $newQteProduit = $produit->getQtStock() + $sortieDepot->getQtSortie();
                    $produit->setQtStock($newQteProduit);

                }
            }
            $newQte = $d->getStock() - $sortieDepot->getQtSortie();
            $d->setStock($newQte);

            $produitRepository->save($produit, true);

            $depotRepository->save($d, true);

            $sortieDepotRepository->save($sortieDepot, true);
            $this->addFlash('success', 'La sortie a été enregistré avec succès.');
            return $this->redirectToRoute('app_sortie_depot_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('sortie_depot/new.html.twig', [
            'sortie_depot' => $sortieDepot,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_sortie_depot_show', methods: ['GET'])]
    public function show(SortieDepot $sortieDepot): Response
    {
        return $this->render('sortie_depot/show.html.twig', [
            'sortie_depot' => $sortieDepot,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_sortie_depot_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, SortieDepot $sortieDepot, SortieDepotRepository $sortieDepotRepository): Response
    {
        $form = $this->createForm(SortieDepotType::class, $sortieDepot);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $sortieDepotRepository->save($sortieDepot, true);

            return $this->redirectToRoute('app_sortie_depot_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('sortie_depot/edit.html.twig', [
            'sortie_depot' => $sortieDepot,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_sortie_depot_delete', methods: ['POST'])]
    public function delete(Request $request, SortieDepot $sortieDepot, SortieDepotRepository $sortieDepotRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$sortieDepot->getId(), $request->request->get('_token'))) {
            $sortieDepotRepository->remove($sortieDepot, true);
        }

        return $this->redirectToRoute('app_sortie_depot_index', [], Response::HTTP_SEE_OTHER);
    }
}
