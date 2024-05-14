<?php

namespace App\Controller;

use App\Entity\Entree;
use App\Entity\Produit;
use App\Repository\EntreeRepository;
use App\Repository\ProduitRepository;
use App\Service\EntreeValidatorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;


class EntreeController extends AbstractController
{
    #[Route('/entree/liste', name: 'entree_liste')]
    public function index(EntreeRepository $entreeRepository, PaginatorInterface $paginator, Request $request, ProduitRepository $detail): Response
    {
        $produits = $detail->findAllOrderedByDate();

        // Assuming you have a method in EntreeRepository to get all entries ordered by date
        $pagination = $paginator->paginate(
            $entreeRepository->findAllOrderedByDate(), // Corrected variable name and method call
            $request->query->get('page', 1),
            10
        );
        
        return $this->render('entree/liste.html.twig', [
            'controller_name' => 'EntreeController',
            'pagination'=> $pagination,
            'produits' => $produits,

        ]);

        return $this->render('entree/liste.html.twig');
    }

    #[Route('/entree/add', name: 'entree_add')]
    public function add(EntityManagerInterface $manager, Request $request, EntreeValidatorService $validatorService): Response
    {
            if ($request->isMethod('POST')) {
                // Get the data from the request
                $produitId = $request->request->get('produit_id');
                $qtEntree = $request->request->get('qt_sortie');
                $prixUnit = $request->request->get('prix_unit');

                $validationErrors = $validatorService->validate([
                    'produitId' => $produitId,
                    'qtSortie' => $qtEntree,
                    'prixUnit' => $prixUnit,
                ]);

                if (!empty($validationErrors)) {
                    foreach ($validationErrors as $error) {
                        $this->addFlash('danger', $error);
                    }
                    return $this->redirectToRoute('entree_liste');
                }

                if (!empty($produitId)){
                    $entree = new Entree();
                    $date = new \DateTime();
                    $entree->setDateEntree($date);
                    $entree->setQtEntree($qtEntree);
                    $entree->setPrixUnit($prixUnit);

                    $produit = $manager->getRepository(Produit::class)->find($produitId);
                    if (!$produit) {
                        $this->addFlash('danger', 'Produit not found.');
                        return $this->redirectToRoute('entree_liste');
                    }

                    $qtStock = $produit->getQtStock();

                        $entree->setProduit($produit);
                        $entree->setTotal($prixUnit * $qtEntree);
                        $user = $this->getUser();
                        $entree->setUser($user);

                        $manager->persist($entree);
                        $manager->flush();

                        // Mise à jour qtestock produit

                        $produit->setQtStock($qtStock + $qtEntree);
                        $produit->setTotal($produit->getPrixUnit() * $produit->getQtStock());

                        $manager->persist($produit);
                        $manager->flush();

                        $this->addFlash('success', 'Le produit a été enregistré avec succès.');
                }
            return $this->redirectToRoute('entree_liste');
        } 
    }

    #[Route('/entree/delete/{id}', name: 'entrer_delete')]
    public function delete(Entree $entree, EntreeRepository $repository, EntityManagerInterface $manager): Response
    {
        $repository->remove($entree, true);

        $manager->flush();

        $this->addFlash('success', 'Le produit entrée a été supprimé avec succès');
        return $this->redirectToRoute('entree_liste');
    }
}
