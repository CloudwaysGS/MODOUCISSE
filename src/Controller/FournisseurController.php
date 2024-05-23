<?php

namespace App\Controller;

use App\Entity\Fournisseur;
use App\Form\FournisseurType;
use App\Repository\FournisseurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FournisseurController extends AbstractController
{
    #[Route('/fournisseur', name: 'fournisseur_liste')]
    public function index(FournisseurRepository $fournisseur, Request $request): Response
    {
        $c = new Fournisseur();
        $form = $this->createForm(FournisseurType::class, $c, array(
            'action' => $this->generateUrl('fournisseur_add'),
        ));
        $page = $request->query->getInt('page', 1); // current page number
        $limit = 10; // number of products to display per page
        $client = $fournisseur->findAllOrderedByDate();
        $total = count($client);
        $offset = ($page - 1) * $limit;
        $client = array_slice($client, $offset, $limit);
        return $this->render('fournisseur/index.html.twig', [
            'controller_name' => 'FournisseurController',
            'client'=>$client,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'form' => $form->createView()
        ]);
        return $this->render('fournisseur/index.html.twig');
    }

    #[Route('/fournisseur/add', name: 'fournisseur_add')]
    public function add(EntityManagerInterface $manager, Request $request): Response
    {
        $fournisseur = new Fournisseur();
        $date = new \DateTime();
        $fournisseur->setDate($date);
        $form = $this->createForm(FournisseurType::class, $fournisseur);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $manager->persist($fournisseur);
            $manager->flush();
            $this->addFlash('success', 'Fournisseur enregistré avec succès.');
        }
        return $this->redirectToRoute('fournisseur_liste');
    }

    #[Route('/fournisseur/edit/{id}', name: 'edit_fournisseur')]
    public function edit($id, FournisseurRepository $repository, Request $request, EntityManagerInterface $entityManager)
    {
        $client = $repository->find($id);
        $form = $this->createForm(FournisseurType::class, $client);
        $form->handleRequest($request);
        $total = $repository->count([]);
        $page = $request->query->getInt('page', 1); // current page number
        $limit = 10; // number of products to display per page
        $offset = ($page - 1) * $limit;

        if($form->isSubmitted() && $form->isValid()){
            $entityManager->persist($form->getData());
            $entityManager->flush();
            return $this->redirectToRoute("fournisseur_liste");
        }
        return $this->render('fournisseur/index.html.twig',[
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'client' => $client,
            'form' => $form->createView()
        ]);
    }

    #[Route('/fournisseur/delete/{id}', name: 'fournisseur_delete')]
    public function delete(Fournisseur $client, FournisseurRepository $repository, EntityManagerInterface $entityManager){
        $dettes = $client->getDetteFounisseur(); // récupérer toutes les dettes associées à ce client
        foreach($dettes as $dette){
            if ($dette->getStatut() != 'non-payée'){
                $entityManager->remove($dette); // supprimer chaque dette associée
            }else{
                $this->addFlash('danger', $dette->getFournisseur()->getNom().' n\'a pas encore réglé sa dette');
                return $this->redirectToRoute('fournisseur_liste');
            }
        }
        $paiements = $client->getPayoffSupplier(); // récupérer tous les paiements associés à ce client
        foreach($paiements as $paiement){
            $entityManager->remove($paiement); // supprimer chaque paiement associé
        }
        $entityManager->remove($client); // supprimer le client après avoir supprimé toutes les dettes associées
        $entityManager->flush();
        $this->addFlash('success', 'Le fournisseur a été supprimé avec succès');
        return $this->redirectToRoute('fournisseur_liste');
    }

}