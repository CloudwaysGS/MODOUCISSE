<?php

namespace App\Entity;

use App\Repository\ProduitRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProduitRepository::class)]
class Produit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\Column(type: ("float"))]
    private ?float $qtStock = null;

    #[ORM\OneToMany(mappedBy: 'produit', targetEntity: Entree::class, orphanRemoval: true)]
    private Collection $entrees;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private $releaseDate;

    #[ORM\ManyToOne(inversedBy: 'produits')]
    private ?User $user = null;

    #[ORM\Column(type: ("float"))]
    private ?float $total = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: '0',nullable: true)]
    private ?string $prixUnit = null;

    #[ORM\ManyToMany(targetEntity: Facture::class, mappedBy: 'produit')]
    private Collection $factures;

    #[ORM\ManyToMany(targetEntity: Facture2::class, mappedBy: 'produit')]
    private Collection $facture2s;

    #[ORM\ManyToOne(inversedBy: 'produits')]
    private ?Client $client = null;

    public function __construct()
    {
        $this->entrees = new ArrayCollection();
        $this->sorties = new ArrayCollection();
        $this->factures = new ArrayCollection();
        $this->facture2s = new ArrayCollection();
        $this->entreeDepots = new ArrayCollection();
        $this->sortieDepots = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): self
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getQtStock(): ?string
    {
        return $this->qtStock;
    }

    public function setQtStock(string $qtStock): self
    {
        $this->qtStock = $qtStock;

        return $this;
    }

    /**
     * @return Collection<int, Entree>
     */
    public function getEntrees(): Collection
    {
        return $this->entrees;
    }

    public function addEntree(Entree $entree): self
    {
        if (!$this->entrees->contains($entree)) {
            $this->entrees->add($entree);
            $entree->setProduit($this);
        }

        return $this;
    }

    public function removeEntree(Entree $entree): self
    {
        if ($this->entrees->removeElement($entree)) {
            // set the owning side to null (unless already changed)
            if ($entree->getProduit() === $this) {
                $entree->setProduit(null);
            }
        }

        return $this;
    }

    /**
     * @param mixed $releaseDate
     */
    public function setReleaseDate($releaseDate): void
    {
        $this->releaseDate = $releaseDate;
    }

    /**
     * @return mixed
     */
    public function getReleaseDate()
    {
        return $this->releaseDate;
    }

    public function __toString(): string
    {
        // TODO: Implement __toString() method.
        return $this->libelle;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getTotal(): ?string
    {
        return $this->total;
    }

    public function setTotal(string $total): self
    {
        $this->total = $total;

        return $this;
    }

    public function getPrixUnit(): ?string
    {
        return $this->prixUnit;
    }

    public function setPrixUnit(string $prixUnit): self
    {
        $this->prixUnit = $prixUnit;

        return $this;
    }

    /**
     * @return Collection<int, Facture>
     */
    public function getFactures(): Collection
    {
        return $this->factures;
    }

    public function addFacture(Facture $facture): self
    {
        if (!$this->factures->contains($facture)) {
            $this->factures->add($facture);
            $facture->addProduit($this);
        }

        return $this;
    }

    public function removeFacture(Facture $facture): self
    {
        if ($this->factures->removeElement($facture)) {
            $facture->removeProduit($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Facture2>
     */
    public function getFacture2s(): Collection
    {
        return $this->facture2s;
    }

    public function addFacture2(Facture2 $facture2): self
    {
        if (!$this->facture2s->contains($facture2)) {
            $this->facture2s->add($facture2);
            $facture2->addProduit($this);
        }

        return $this;
    }

    public function removeFacture2(Facture2 $facture2): self
    {
        if ($this->facture2s->removeElement($facture2)) {
            $facture2->removeProduit($this);
        }

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): self
    {
        $this->client = $client;

        return $this;
    }

}