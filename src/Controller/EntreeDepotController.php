<?php

namespace App\Controller;

use App\Entity\Depot;
use App\Entity\EntreeDepot;
use App\Entity\Produit;
use App\Form\EntreeDepotType;
use App\Repository\DepotRepository;
use App\Repository\EntreeDepotRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/entree/depot')]
class EntreeDepotController extends AbstractController
{
    #[Route('/', name: 'app_entree_depot_index', methods: ['GET'])]
    public function index(EntreeDepotRepository $entreeDepotRepository, PaginatorInterface $paginator, Request $request): Response
    {

        $pagination = $paginator->paginate(
            $entreeDepotRepository->findAllOrderedByDate(),
            $request->query->get('page', 1),
            10
        );
        return $this->render('entree_depot/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_entree_depot_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntreeDepotRepository $entreeDepotRepository, EntityManagerInterface $manager, DepotRepository $depotRepository): Response
    {
        $entreeDepot = new EntreeDepot();
        $form = $this->createForm(EntreeDepotType::class, $entreeDepot);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $depot = $entreeDepot->getDepot();
            if (!$depot) {
                $this->addFlash('danger', 'Veuillez sélectionner un dépôt.');
                return $this->redirectToRoute('app_sortie_depot_new');
            }
            $d = $manager->getRepository(Depot::class)->find($entreeDepot->getDepot()->getId());
            $nomProduit = $depot->getLibelle();
            $date = new \DateTime();
            $entreeDepot->setReleaseDate($date);
            $entreeDepot->setLibelle($nomProduit);

            //****Mise à jour****//

            $newQte = $d->getStock() + $entreeDepot->getQtEntree();
            $d->setStock($newQte);
            $depotRepository->save($d, true);

            $entreeDepotRepository->save($entreeDepot, true);
            $this->addFlash('success', 'L\'entree a été enregistré avec succès.');

            return $this->redirectToRoute('app_entree_depot_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('entree_depot/new.html.twig', [
            'entree_depot' => $entreeDepot,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_entree_depot_show', methods: ['GET'])]
    public function show(EntreeDepot $entreeDepot): Response
    {
        return $this->render('entree_depot/show.html.twig', [
            'entree_depot' => $entreeDepot,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_entree_depot_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EntreeDepot $entreeDepot, EntreeDepotRepository $entreeDepotRepository): Response
    {
        $form = $this->createForm(EntreeDepotType::class, $entreeDepot);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entreeDepotRepository->save($entreeDepot, true);

            return $this->redirectToRoute('app_entree_depot_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('entree_depot/edit.html.twig', [
            'entree_depot' => $entreeDepot,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_entree_depot_delete', methods: ['POST'])]
    public function delete(Request $request, EntreeDepot $entreeDepot, EntreeDepotRepository $entreeDepotRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$entreeDepot->getId(), $request->request->get('_token'))) {
            $entreeDepotRepository->remove($entreeDepot, true);
        }

        return $this->redirectToRoute('app_entree_depot_index', [], Response::HTTP_SEE_OTHER);
    }
}
