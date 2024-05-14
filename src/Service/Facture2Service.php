<?php
namespace App\Service;

use App\Entity\Client;
use App\Entity\Facture2;
use App\Entity\Produit;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;


class Facture2Service
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }
    public function createFacture2( $id, $quantity, $clientId, $user, $actionType, $quantityDetail = 1, $clientIdDetail = null)
    {
        $factures = $this->entityManager->getRepository(Facture2::class)->findBy(['etat' => 1]);

        if (empty($factures) && empty($clientId) && empty($clientIdDetail)) {
            throw new \Exception('Veuillez choisir un client.');
        }

        if (!empty($factures)) {
            $lastFacture = end($factures);
            $user = $this->security->getUser();

            if ("{$user->getPrenom()} {$user->getNom()}" !== $lastFacture->getConnect()) {
                throw new \Exception('La facture est verrouillée pour le moment, veuillez accéder à +Facture.');
            }
        }


        if ($actionType === 'addToFactureDetail') {
            $produit = $this->entityManager->getRepository(Produit::class)->find($id);
            $facture = (new Facture2())
                ->addProduit($produit)
                ->setQuantite($quantityDetail);
            $client = $this->entityManager->getRepository(Client::class)->find($clientIdDetail);
            if ($client !== null) {
                $facture->setClient($client);
                $facture->setNomClient($client->getNom());
            }

            $produitInFacture = $facture->getProduit()->first();
            $facture->setClient($client);
            $facture->setNomProduit($produitInFacture->getNomProduitDetail());
            $facture->setPrixUnit($produitInFacture->getPrixDetail());
            $facture->setMontant($produitInFacture->getPrixDetail() * $facture->getQuantite());
            $facture->setNombre($produitInFacture->getNombre());
            $facture->setNombreVendus('0');
            $facture->setDate(new \DateTime());
            $facture->setConnect($user->getPrenom() . ' ' . $user->getNom());

            $existingProduit = $this->entityManager->getRepository(Facture2::class)
                ->findOneBy(['nomProduit' => $facture->getNomProduit(), 'etat' => 1]);
            if ($existingProduit && $this->compareStrings($existingProduit->getNomProduit(), $facture->getNomProduit())) {
                throw new \Exception($facture->getNomProduit() . ' a déjà été ajouté précédemment.');
            }

            $p = $this->entityManager->getRepository(Produit::class)->find($produit);
            if ($p->getqtStockDetail() < $facture->getQuantite()) {
                throw new \Exception('La quantité en stock est insuffisante pour satisfaire la demande. Quantité stock : ' . $p->getqtStockDetail());
            } else if ($facture->getQuantite() <= 0) {
                throw new \Exception('Entrez une quantité positive, s\'il vous plaît !');
            }

            $this->entityManager->persist($facture);
            $this->entityManager->flush();

            if ($facture->getNombre() !== null){
                $quantite = floatval($facture->getQuantite());
                $nombre = $facture->getNombre();
                $vendus = $facture->getNombreVendus();
                if ($quantite >= $nombre) {
                    $boxe = $quantite / $nombre;
                    $vendus += $boxe;
                    $dstock = $p->getQtStock() - $vendus;
                    $p->setQtStock($dstock);
                    $p->setNbreVendu($vendus);
                }else{
                    $boxe = $quantite / $nombre;
                    $vendus += $boxe;
                    $dstock = $p->getQtStock() - $vendus;
                    $p->setQtStock($dstock);
                    $p->setNbreVendu($vendus);
                }

                $upd = $p->getQtStockDetail() - $facture->getQuantite();
                $p->setQtStockDetail($upd);
                $upddd = $p->getQtStock() * $p->getPrixUnit();
                $p->setTotal($upddd);
            }

            $this->entityManager->flush();

            return $facture;
        }

        $produit = $this->entityManager->getRepository(Produit::class)->find($id);
        $facture = (new Facture2())
            ->addProduit($produit)
            ->setQuantite($quantity);
        $client = $this->entityManager->getRepository(Client::class)->find($clientId);

        if ($client !== null) {
            $facture->setClient($client);
            $facture->setNomClient($client->getNom());
        }

        $produitInFacture = $facture->getProduit()->first();
        $facture->setClient($client);
        $facture->setNomProduit($produitInFacture->getLibelle());
        $facture->setPrixUnit($produitInFacture->getPrixUnit());
        $facture->setMontant($produitInFacture->getPrixUnit() * $facture->getQuantite());
        $facture->setDate(new \DateTime());
        $facture->setConnect($user->getPrenom() . ' ' . $user->getNom());

        $p = $this->entityManager->getRepository(Produit::class)->find($produit);
        if ($p->getQtStock() < $facture->getQuantite()) {
            throw new \Exception('La quantité en stock est insuffisante pour satisfaire la demande. Quantité stock : ' . $p->getQtStock());
        } else if ($facture->getQuantite() <= 0) {
            throw new \Exception('Entrez une quantité positive, s\'il vous plaît !');
        }

        $existingProduit = $this->entityManager->getRepository(Facture2::class)
            ->findOneBy(['nomProduit' => $facture->getNomProduit(), 'etat' => 1]);
        if ($existingProduit && $this->compareStrings($existingProduit->getNomProduit(), $facture->getNomProduit())) {
            throw new \Exception($facture->getNomProduit() . ' a déjà été ajouté précédemment.');
        }
        $this->entityManager->persist($facture);
        $this->entityManager->flush();

        //Mise à jour quantité produit et total produit
        $dstock = $p->getQtStock() - $facture->getQuantite();
        $p->setQtStock($dstock);
        $upddd = $p->getQtStock() * $p->getPrixUnit();
        if ($p->getNombre() != null){
            $p->setQtStockDetail($p->getNombre() * $p->getQtStock());
        }
        $p->setTotal($upddd);
        $this->entityManager->persist($p);
        $this->entityManager->flush();

        return $facture;

    }

    private function compareStrings(string $str1, string $str2): bool
    {
        $str1 = str_replace(' ', '', strtolower($str1));
        $str2 = str_replace(' ', '', strtolower($str2));
        return $str1 === $str2;
    }

    public function updateTotalForFactures()
    {
        $factures = $this->entityManager->getRepository(Facture2::class)->findBy(['etat' => 1]);
        $total = 0;

        foreach ($factures as $facture) {
            $total += $facture->getMontant();
        }

        foreach ($factures as $facture) {
            $facture->setTotal($total);
            $this->entityManager->persist($facture);
        }

        $this->entityManager->flush();

        return $total;
    }
}