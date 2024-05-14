<?php

// src/Service/ProduitAddService.php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use App\Entity\Produit;
use Symfony\Component\Security\Core\Security;

class ProduitAddService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    public function handleFormSubmission(FormInterface $form): void
    {
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $libelleProduit = $data->getLibelle();
            $existingProduit = $this->entityManager->getRepository(Produit::class)
                ->findOneBy(['libelle' => $libelleProduit]);

            if ($existingProduit && $this->compareStrings($existingProduit->getLibelle(), $libelleProduit)) {
                throw new \Exception( 'Un produit avec ce nom existe déjà.');
            }

            $user = $this->getUser() ?? throw new \Exception( 'Aucun utilisateur n\'est actuellement connecté.');
            $data->setUser($user);
            $data->setReleaseDate(new \DateTime());
            //$data->setQtStockDetail($data->getNombre() * $data->getQtStock());
            $montant = $data->getQtStock() * $data->getPrixUnit();
            $data->setTotal($montant);
            //$data->setNbreVendu('0');
            $majusculeLibelle = strtolower($data->getLibelle());
            //$majusculeDetail = strtolower($data->getNomProduitDetail());
            $data->setLibelle($majusculeLibelle);
            

            $this->entityManager->persist($data);
            $this->entityManager->flush();

            throw new \Exception( 'Le produit a été ajouté avec succès.');
        }
    }

    private function getUser()
    {
        $user = $this->security->getUser();
        return $user;
    }

    private function compareStrings($str1, $str2)
    {
        $str1 = str_replace(' ', '', strtolower($str1));
        $str2 = str_replace(' ', '', strtolower($str2));
        return strtolower($str1) === strtolower($str2);
    }
}
