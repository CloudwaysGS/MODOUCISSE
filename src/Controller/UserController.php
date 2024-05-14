<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use MercurySeries\FlashyBundle\FlashyNotifier;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{

    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    #[Route('/user', name: 'user_liste')]
    public function index(UserRepository $user, FlashyNotifier $flashy): Response
    {
        $user = $user->findAll();
        $flashy->success('Bonjour');
        return $this->render('user/liste.html.twig', [
            'controller_name' => 'UserController',
            'user' => $user
        ]);
    }

    #[Route('/user_create', name: 'add_user')]
    public function add_class(EntityManagerInterface $manager,
                              Request $request,
                              FlashyNotifier $flashy,
                              UserRepository $userRepository): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $existingUser = $userRepository->findBy(['email' => $user->getEmail()]);
            if (!empty($existingUser)) {
                $this->addFlash('danger','Cet utilisateur existe déjà');
                return $this->redirectToRoute("user_liste");
            }

            $plainPassword = $user->getPassword();
            $user->setPassword($this->hasher->hashPassword($user, $plainPassword));
            $flashy->success('Utilisateur a été créé avec success');
            $manager->persist($user);
            $manager->flush();

            return $this->redirectToRoute("user_liste");
        }

        return $this->render('user/form.html.twig', [
            'form' => $form->createView()
        ]);
    }



    #[Route('/user/{id}', name: 'user_details')]
    public function detailsAction(UserRepository $repo, $id)
    {
        $user = $repo->find($id);
        return $this->render('user/liste.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/user/delete/{id}', name: 'user_delete')]
    public function delete(User $user, UserRepository $repository, EntityManagerInterface $entityManager){
        $entityManager->remove($user); // supprimer le client après avoir supprimé toutes les dettes associées
        $entityManager->flush();
        $this->addFlash('success', 'Le client a été supprimé avec succès');
        return $this->redirectToRoute('user_liste');
    }


}
