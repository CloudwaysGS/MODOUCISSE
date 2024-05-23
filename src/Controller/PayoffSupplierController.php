<?php

namespace App\Controller;

use App\Entity\PayoffSupplier;
use App\Entity\Search;
use App\Form\PayoffSupplierType;
use App\Form\SearchType;
use App\Repository\PayoffSupplierRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PayoffSupplierController extends AbstractController
{
    #[Route('/payoff/supplier', name: 'payoff_supplier_liste')]
    public function index(PayoffSupplierRepository $paiement, Request $request): Response
    {
        $p = new PayoffSupplier();
        $form = $this->createForm(PayoffSupplierType::class, $p, array(
            'action' => $this->generateUrl('payoff_supplier_add'),
        ));

        $search = new Search();
        $form2 = $this->createForm(SearchType::class, $search);
        $form2->handleRequest($request);
        $nom = $search->getNom();
        $page = $request->query->getInt('page', 1); // current page number
        $limit = 10; // number of products to display per page
        $total = $nom ? count($paiement->findByName($nom)) : $paiement->countAll();
        $offset = ($page - 1) * $limit;
        $paiements = $nom ? $paiement->findByName($nom, $limit, $offset) : $paiement->findAllOrderedByDate($limit, $offset);
        return $this->render('payoff_supplier/index.html.twig', [
            'controller_name' => 'PayoffSupplierController',
            'paiements'=>$paiements,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'form' => $form->createView(),
            'form2' => $form2->createView()
        ]);
    }

    #[Route('/payoff_supplier/add', name: 'payoff_supplier_add')]
    public function add(EntityManagerInterface $manager, Request $request): Response
    {
        $payment = new PayoffSupplier();
        $date = new \DateTime();
        $payment->setDatePaiement($date);
        $form = $this->createForm(PayoffSupplierType::class, $payment);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->redirectToRoute('payoff_supplier_liste');
        }

        $client = $payment->getFournisseur();
        $currentDebt = $client->getDetteFounisseur()->first();
        $remainingDebt = (!$currentDebt || !method_exists($currentDebt, 'getReste')) ? null : $currentDebt->getReste();
        if (is_null($remainingDebt)) {
            $this->addFlash('danger','Aucune dette n\'a été trouvée pour ce fournisseur.');
            return $this->redirectToRoute('payoff_supplier_liste');
        }

        $paymentAmount = $payment->getMontant();

        if ($currentDebt->getStatut() == 'payée') {
            $this->addFlash('success','La dette a déjà été réglée.');
            return $this->redirectToRoute('payoff_supplier_liste');
        }

        $remainingDebt -= $paymentAmount;

        if ($remainingDebt < 0) {
            $this->addFlash('danger',$client->getNom().' a payé plus que ce qu\'il devait et on doit lui  rembourser '.abs($remainingDebt).' F');
            $currentDebt->setStatut('payée');
            $currentDebt->setReste($remainingDebt);
            $payment->setReste('0');
            $manager->persist($payment);
            $manager->flush();
            return $this->redirectToRoute('payoff_supplier_liste');
        }
        if ($remainingDebt == 0){
            $currentDebt->setStatut('payée');
            $this->addFlash('success','La dette a été payée.');
        }

        $currentDebt->setReste($remainingDebt);
        $payment->setReste($remainingDebt);

        $manager->persist($payment);
        $manager->flush();

        $this->addFlash('success','Le paiement a été enregistré avec succès.');
        return $this->redirectToRoute('payoff_supplier_liste');
    }

    #[Route('/payoff_supplier/edit/{id}', name: 'payoff_supplier_edit')]
    public function edit($id, PayoffSupplierRepository $repository, Request $request, EntityManagerInterface $entityManager)
    {
        $paiement = $repository->find($id);
        $search = new Search();
        $form = $this->createForm(PayoffSupplierType::class, $paiement);
        $form2 = $this->createForm(SearchType::class, $search);
        $total = $repository->count([]);
        $page = $request->query->getInt('page', 1); // current page number
        $limit = 10; // number of products to display per page
        $offset = ($page - 1) * $limit;
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $entityManager->persist($form->getData());
            $entityManager->flush();
            return $this->redirectToRoute("payoff_supplier_liste");
        }
        return $this->render('payoff_supplier/index.html.twig',[
            'paiements' => $paiement,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'form' => $form->createView(),
            'form2' => $form2->createView()
        ]);
    }

    #[Route('/payoff_supplier/delete/{id}', name: 'payoff_supplier_delete')]
    public function delete(PayoffSupplier $paiement, EntityManagerInterface $entityManager){
        $entityManager->remove($paiement); // supprimer le client après avoir supprimé toutes les dettes associées
        $entityManager->flush();
        $this->addFlash('success', 'Le paiement a été supprimé avec succès');
        return $this->redirectToRoute('payoff_supplier_liste');
    }
}