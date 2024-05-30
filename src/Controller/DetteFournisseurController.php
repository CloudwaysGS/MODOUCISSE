<?php

namespace App\Controller;

use App\Entity\DetteFournisseur;
use App\Entity\Fournisseur;
use App\Entity\Search;
use App\Form\DetteFournisseurType;
use App\Form\SearchType;
use App\Repository\DetteFournisseurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use MercurySeries\FlashyBundle\FlashyNotifier;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class DetteFournisseurController extends AbstractController
{
    #[Route('/dette/founisseur', name: 'dette_founisseur_liste')]
    public function index(DetteFournisseurRepository $detteFournisseurRepository, Request $request, PaginatorInterface $paginator): Response
    {
        $d = new DetteFournisseur();
        $form = $this->createForm(DetteFournisseurType::class, $d, array(
            'action' => $this->generateUrl('detteFournisseur_add'),
        ));
        $search = new Search();
        $form2 = $this->createForm(SearchType::class, $search);
        $form2->handleRequest($request);
        $nom = $search->getNom();
        $pagination = $paginator->paginate(
            ($nom !== null && $nom !== '') ? $detteFournisseurRepository->findByName($nom) : $detteFournisseurRepository->findAllOrderedByDate(),
            $request->query->get('page', 1),
            10
        );

        // Calcul de la somme des dettes non-payées
        $totalNonPaid = $detteFournisseurRepository->findNonPaidTotal();

        return $this->render('dette_fournisseur/liste.html.twig', [
            'controller_name' => 'DetteController',
            'totalNonPaid' => $totalNonPaid,
            'pagination' => $pagination,
            'form' => $form->createView(),
            'form2' => $form2->createView(),
        ]);
    }

    #[Route('/detteFournisseur/add', name: 'detteFournisseur_add')]
    public function add(EntityManagerInterface $manager, Request $request, FlashyNotifier $notifier, Security $security): Response
    {
        // Vérifiez si l'utilisateur est connecté
        $user = $security->getUser();
        if (!$user) {
            // Redirigez l'utilisateur vers la page de connexion si non connecté
            return $this->redirectToRoute('app_login');
        }

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

        $infos = $entityManager->getRepository(DetteFournisseur::class)->find($id);
        // Renvoie les informations dans la vue du modal
        return $this->render('dette_fournisseur/detail.html.twig', [
            'infos' => $infos,
        ]);
    }

    #[Route('/fournisseur/edit/{id}', name: 'edit_dettefournisseur')]
    public function edit($id,DetteFournisseurRepository $detteRepository,Request $request,EntityManagerInterface $entityManager, PaginatorInterface $paginator): Response
    {

        $dette =$detteRepository->find($id);
        if (!$dette) {
            throw $this->createNotFoundException('La dette n\'existe pas.');
        }

        $form = $this->createForm(DetteFournisseurType::class, $dette);
        $form->handleRequest($request);
        $search = new Search();
        $form2 = $this->createForm(SearchType::class, $search);
        $pagination = $paginator->paginate(
            $detteRepository->findAllOrderedByDate(),
            $request->query->get('page', 1),
            10
        );
        if($form->isSubmitted() && $form->isValid()){

            $dette->setReste($dette->getMontantDette());
            $entityManager->persist($dette);
            $entityManager->flush();
            $this->addFlash('success', 'Dette modifié avec succès');

            return $this->redirectToRoute("dette_founisseur_liste");
        }

        $sommeMontantImpaye = $detteRepository->findNonPaidTotal();

        $this->addFlash('warning', 'MODIFICATION');

        return $this->render('dette_fournisseur/liste.html.twig', [
            'pagination'=>$pagination,
            'totalNonPaid' => $sommeMontantImpaye,
            'form' => $form->createView(),
            'form2' => $form2->createView(),
        ]);
    }

}