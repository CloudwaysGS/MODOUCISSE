<?php

namespace App\Controller;

use App\Entity\DetteFournisseur;
use App\Entity\Fournisseur;
use App\Form\DetteFournisseurType;
use App\Repository\DetteFournisseurRepository;
use Doctrine\ORM\EntityManagerInterface;
use MercurySeries\FlashyBundle\FlashyNotifier;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DetteFournisseurController extends AbstractController
{
    #[Route('/dette/founisseur', name: 'dette_founisseur_liste')]
    public function index(DetteFournisseurRepository $dette, Request $request): Response
    {
        $d = new DetteFournisseur();
        $form = $this->createForm(DetteFournisseurType::class, $d, array(
            'action' => $this->generateUrl('detteFournisseur_add'),
        ));
        $page = $request->query->getInt('page', 1); // current page number
        $limit = 10; // number of products to display per page
        $dette = $dette->findAllOrderedByDate();
        $total = count($dette);
        $offset = ($page - 1) * $limit;
        $dette = array_slice($dette, $offset, $limit);
        return $this->render('dette_fournisseur/liste.html.twig', [
            'controller_name' => 'DetteController',
            'dette'=>$dette,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'form' => $form->createView()
        ]);
        return $this->render('dette_founisseur/liste.html.twig');
    }

    #[Route('/detteFournisseur/add', name: 'detteFournisseur_add')]
    public function add(EntityManagerInterface $manager, Request $request, FlashyNotifier $notifier): Response
    {

        $dette = new DetteFournisseur();
        $form = $this->createForm(DetteFournisseurType::class, $dette);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $client = $dette->getFournisseur();
            $client = $manager->getRepository(Fournisseur::class)->find($client->getId());
            if ($client) {
                $dette->setFournisseur($client)
                    ->setDate(new \DateTime())
                    ->setReste($dette->getMontantDette())
                    ->setStatut('non-payée');
            }
            $manager->persist($dette);
            $manager->flush();
            $notifier->success('L\'entrée a été enregistrée avec succès.');
        }
        return $this->redirectToRoute('dette_founisseur_liste');
    }

    #[Route('/dette_founisseur/delete/{id}', name: 'dette_founisseur_delete')]
    public function delete(DetteFournisseur $dette, DetteFournisseurRepository $repository){
        if ($dette->getStatut() != 'payée'){
            $this->addFlash('danger', 'La dette n\'a pas encore été réglée.');
            return $this->redirectToRoute('dette_founisseur_liste');
        }
        $repository->remove($dette,true);
        $this->addFlash('success', 'La dette a été supprimé avec succès');
        return $this->redirectToRoute('dette_founisseur_liste');
    }

    #[Route('/dette_fournisseur/info/{id}', name: 'dette_fournisseur_info')]
    public function info(DetteFournisseur $dette, EntityManagerInterface $entityManager, Request $request, $id)
    {
        $fournisseur = $dette->getFournisseur()->getNom();
        $montant = $dette->getMontantDette();
        $reste = $dette->getReste();
        $statut = $dette->getStatut();
        $comment = $dette->getCommentaire();
        // Renvoie les informations dans la vue du modal
        return $this->render('dette_fournisseur/detail.html.twig', [
            'fournisseur' => $fournisseur,
            'montant' => $montant,
            'reste' => $reste,
            'statut' => $statut,
            'comment' => $comment,
        ]);
    }

}