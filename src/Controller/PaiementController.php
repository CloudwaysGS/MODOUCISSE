<?php

namespace App\Controller;

use App\Entity\Chargement;
use App\Entity\Dette;
use App\Entity\Paiement;
use App\Entity\Search;
use App\Form\PaiementType;
use App\Form\SearchType;
use App\Repository\PaiementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;

class PaiementController extends AbstractController
{
    #[Route('/paiement', name: 'paiement_liste')]
    public function index(PaiementRepository $paiement, Request $request, PaginatorInterface $paginator): Response
    {
        $p = new Paiement();
        $form = $this->createForm(PaiementType::class, $p, array(
            'action' => $this->generateUrl('paiement_add'),
        ));

        $search = new Search();
        $form2 = $this->createForm(SearchType::class, $search);
        $form2->handleRequest($request);
        $nom = $search->getNom();
        $pagination = $paginator->paginate(
            ($nom !== null && $nom !== '') ? $paiement->findByName($nom) : $paiement->findAllOrderedByDate(),
            $request->query->get('page', 1),
            10
        );
        return $this->render('paiement/index.html.twig', [
            'controller_name' => 'ClientController',
            'pagination' => $pagination,
            'form' => $form->createView(),
            'form2' => $form2->createView()
        ]);
    }


    #[Route('/paiement/add', name: 'paiement_add')]
    public function add(EntityManagerInterface $manager, Request $request): Response
    {

        $payment = new Paiement();
        $date = new \DateTime();
        $payment->setDatePaiement($date);
        $form = $this->createForm(PaiementType::class, $payment);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->redirectToRoute('paiement_liste');
        }

        $client = $payment->getClient();
        $currentDebt = $client->getDette()->last();
        $remainingDebt = (!$currentDebt || !method_exists($currentDebt, 'getReste')) ? null : $currentDebt->getReste();
        if (is_null($remainingDebt)) {
            $this->addFlash('danger','Aucune dette n\'a été trouvée pour '.$client->getNom().'.');
            return $this->redirectToRoute('paiement_liste');
        }

        $paymentAmount = $payment->getMontant();
        if ($currentDebt->getStatut() == 'payée') {
            $this->addFlash('danger','La dette a déjà été réglée.');
            return $this->redirectToRoute('paiement_liste');
        }

        $remainingDebt -= $paymentAmount;
        if ($remainingDebt < 0) {
            $this->addFlash('success',$client->getNom().' a payé plus que ce qu\'il devait et on doit lui  rembourser '.abs($remainingDebt).' F');
            $currentDebt->setStatut('payée');
            $currentDebt->setReste($remainingDebt);
            $payment->setReste('0');
            $manager->persist($payment);
            $manager->flush();
            return $this->redirectToRoute('paiement_liste');
        }
        if ($remainingDebt == 0){
            $montantDette = $payment->getClient()->getDette()->first()->getMontantDette();
            $chargement = $manager->getRepository(Chargement::class)->findAll();
            foreach ($chargement as $charge){
                $totalFacture = $charge->getTotal();
                $nomFact = $payment->getClient()->getNom();
                if ($montantDette == $totalFacture && $charge->getNomClient() == $nomFact){
                    $charge->setStatut('payée');
                    $manager->persist($charge);
                    $manager->flush();
                }
            }
            $currentDebt->setStatut('payée');
            $this->addFlash('success','La dette a été payée.');

        }
        $currentDebt->setReste($remainingDebt);
        $payment->setReste($remainingDebt);

        $manager->persist($payment);
        $manager->flush();

        $this->addFlash('success','Le paiement a été enregistré avec succès.');
        return $this->redirectToRoute('paiement_liste');
    }

    #[Route('/paiement/edit/{id}', name: 'paiement_edit')]
    public function edit($id, PaiementRepository $repository, Request $request, EntityManagerInterface $entityManager)
    {
        $paiement = $repository->find($id);
        $search = new Search();
        $form = $this->createForm(PaiementType::class, $paiement);
        $form2 = $this->createForm(SearchType::class, $search);
        $total = $repository->count([]);
        $page = $request->query->getInt('page', 1); // current page number
        $limit = 10; // number of products to display per page
        $offset = ($page - 1) * $limit;
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $entityManager->persist($form->getData());
            $entityManager->flush();
            return $this->redirectToRoute("paiement_liste");
        }
        return $this->render('paiement/index.html.twig',[
            'paiements' => $paiement,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'form' => $form->createView(),
            'form2' => $form2->createView()
        ]);
    }

    #[Route('/paiement/delete/{id}', name: 'paiement_delete')]
    public function delete(Paiement $paiement, EntityManagerInterface $entityManager){
        $entityManager->remove($paiement); // supprimer le client après avoir supprimé toutes les dettes associées
        $entityManager->flush();
        $this->addFlash('success', 'Le paiement a été supprimé avec succès');
        return $this->redirectToRoute('paiement_liste');
    }

    #[Route('/paiement/detail/{id}', name: 'paiement_detail')]
    public function detail(Paiement $paiement, EntityManagerInterface $entityManager): Response
    {
        $client = $paiement->getClient();

        $repository = $entityManager->getRepository(Paiement::class);
        $paiements = $repository->findBy(['client' => $client], ['datePaiement' => 'DESC']);

        $showAll = false;
        $additionalPaiements = [];

        if (count($paiements) > 10) {
            $showAll = true;
            $additionalPaiements = array_slice($paiements, 10); // Récupérer les paiements supplémentaires à partir de l'indice 5
        }
        $paiements = array_slice($paiements,0,10);

        return $this->render('paiement/detail.html.twig', [
            'controller_name' => 'PaiementController',
            'paiements' => $paiements,
            'showAll' => $showAll,
            'additionalPaiements' => $additionalPaiements,
        ]);
    }



}
