<?php

namespace App\Entity;

use App\Repository\DepotRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DepotRepository::class)]
class Depot
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToMany(mappedBy: 'depot', targetEntity: Produit::class)]
    private Collection $produit;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\Column]
    private ?float $stock = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\OneToMany(mappedBy: 'depot', targetEntity: EntreeDepot::class)]
    private Collection $entreeDepots;

    #[ORM\OneToMany(mappedBy: 'depot', targetEntity: SortieDepot::class)]
    private Collection $sortieDepots;

    public function __construct()
    {
        $this->produit = new ArrayCollection();
        $this->entreeDepots = new ArrayCollection();
        $this->depot = new ArrayCollection();
        $this->sortieDepots = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, Produit>
     */
    public function getProduit(): Collection
    {
        return $this->produit;
    }

    public function addProduit(Produit $produit): self
    {
        if (!$this->produit->contains($produit)) {
            $this->produit->add($produit);
            $produit->setDepot($this);
        }

        return $this;
    }

    public function removeProduit(Produit $produit): self
    {
        if ($this->produit->removeElement($produit)) {
            // set the owning side to null (unless already changed)
            if ($produit->getDepot() === $this) {
                $produit->setDepot(null);
            }
        }

        return $this;
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

    public function getStock(): ?float
    {
        return $this->stock;
    }

    public function setStock(float $stock): self
    {
        $this->stock = $stock;

        return $this;
    }

    public function getPrix(): ?string
    {
        return $this->prix;
    }

    public function setPrix(?string $prix): self
    {
        $this->prix = $prix;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    /**
     * @return Collection<int, EntreeDepot>
     */
    public function getEntreeDepots(): Collection
    {
        return $this->entreeDepots;
    }

    public function addEntreeDepot(EntreeDepot $entreeDepot): self
    {
        if (!$this->entreeDepots->contains($entreeDepot)) {
            $this->entreeDepots->add($entreeDepot);
            $entreeDepot->setDepot($this);
        }

        return $this;
    }

    public function removeEntreeDepot(EntreeDepot $entreeDepot): self
    {
        if ($this->entreeDepots->removeElement($entreeDepot)) {
            // set the owning side to null (unless already changed)
            if ($entreeDepot->getDepot() === $this) {
                $entreeDepot->setDepot(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, SortieDepot>
     */
    public function getSortieDepots(): Collection
    {
        return $this->sortieDepots;
    }

    public function addSortieDepot(SortieDepot $sortieDepot): self
    {
        if (!$this->sortieDepots->contains($sortieDepot)) {
            $this->sortieDepots->add($sortieDepot);
            $sortieDepot->setDepot($this);
        }

        return $this;
    }

    public function removeSortieDepot(SortieDepot $sortieDepot): self
    {
        if ($this->sortieDepots->removeElement($sortieDepot)) {
            // set the owning side to null (unless already changed)
            if ($sortieDepot->getDepot() === $this) {
                $sortieDepot->setDepot(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        // TODO: Implement __toString() method.
        return $this->libelle;
    }

}
