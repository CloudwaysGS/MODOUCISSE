<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Entity\Search;
use App\Form\ProduitType;
use App\Form\SearchType;
use App\Repository\ProduitRepository;
use App\Service\ProduitAddService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProduitController extends AbstractController
{


    #[Route('/produit/liste', name: 'produit_liste')]
    public function index(ProduitRepository $prod, Request $request, PaginatorInterface $paginator): Response
    {
        $lastDayOfMonth = new \DateTime('last day of this month');
        $today = new \DateTime();
        $remainingDays = $lastDayOfMonth->diff($today)->days;
        $message = ($remainingDays === 2) ? "Attention : Il ne reste que 2 jours avant la fin du mois en cours !" : (($remainingDays === 1) ? "Attention : Il ne reste plus que 1 jour avant la fin du mois en cours !" : "");

        $p = new Produit();
        $form = $this->createForm(ProduitType::class, $p, [
            'action' => $this->generateUrl('produit_add')
        ]);
        $search = new Search();
        $form2 = $this->createForm(SearchType::class, $search);
        $form2->handleRequest($request);
        $nom = $search->getNom();
        $pagination = $paginator->paginate(
            ($nom !== null && $nom !== '') ? $prod->findByName($nom) : $prod->findAllOrderedByDate(),
            $request->query->get('page', 1),
            10
        );

        return $this->render('produit/liste.html.twig', [
            'controller_name' => 'ProduitController',
            'pagination' => $pagination,
            'form' => $form->createView(),
            'form2' => $form2->createView(),
            'message' => $message
        ]);
    }

    #[Route('/produit/add', name: 'produit_add')]
    public function add(EntityManagerInterface $manager, ProduitAddService $produitAddService, Request $request): Response
    {
        $form = $this->createForm(ProduitType::class);
        $form->handleRequest($request);

        try {

            $produitAddService->handleFormSubmission($form);
            return $this->redirectToRoute('produit_liste');

        } catch (\Exception $e) {
            $this->addFlash('success', $e->getMessage());
            return $this->redirectToRoute('produit_liste');
        }
    }

    #[Route('/produit/delete/{id}', name: 'produit_delete')]
    public function delete(Produit $produit, ProduitRepository $repository){
        $repository->remove($produit,true);
        $this->addFlash('success', 'Le produit a été supprimé avec succès');
        return $this->redirectToRoute('produit_liste');
    }

    #[Route('/produit/edit/{id}', name: 'produit_edit')]
    public function edit($id,ProduitRepository $prod,Request $request,EntityManagerInterface $entityManager, PaginatorInterface $paginator): Response
    {
        $lastDayOfMonth = new \DateTime('last day of this month');
        $today = new \DateTime();
        $remainingDays = $lastDayOfMonth->diff($today)->days;
        $message = ($remainingDays === 2) ? "Attention : Il ne reste que 2 jours avant la fin du mois en cours !" : (($remainingDays === 1) ? "Attention : Il ne reste plus que 1 jour avant la fin du mois en cours !" : "");

        $produits =$prod->find($id);
        $form = $this->createForm(ProduitType::class, $produits);
        $form->handleRequest($request);
        $search = new Search();
        $form2 = $this->createForm(SearchType::class, $search);
        $pagination = $paginator->paginate(
            $prod->findAllOrderedByDate(),
            $request->query->get('page', 1),
            10
        );
        if($form->isSubmitted() && $form->isValid()){
            $update = $form->getData()->getQtStock() * $form->getData()->getPrixUnit();
            
            $form->getData()->setTotal($update);
            $entityManager->persist($form->getData());
           $entityManager->flush();
            $this->addFlash('success', 'produit modifié avec succès');

            return $this->redirectToRoute("produit_liste");
        }

        $this->addFlash('warning', 'MODIFICATION');

        
        return $this->render('produit/liste.html.twig', [
            'pagination'=>$pagination,
            'form' => $form->createView(),
            'form2' => $form2->createView(),
            'message' => $message
        ]);
    }


}
