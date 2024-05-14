<?php

namespace App\Entity;

use App\Repository\FournisseurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FournisseurRepository::class)]
class Fournisseur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $telephone = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\OneToMany(mappedBy: 'fournisseur', targetEntity: PayoffSupplier::class)]
    private Collection $PayoffSupplier;

    #[ORM\OneToMany(mappedBy: 'fournisseur', targetEntity: DetteFournisseur::class)]
    private Collection $DetteFounisseur;

    #[ORM\OneToMany(mappedBy: 'fournisseur', targetEntity: Entree::class)]
    private Collection $Entree;

    public function __construct()
    {
        $this->PayoffSupplier = new ArrayCollection();
        $this->DetteFounisseur = new ArrayCollection();
        $this->Entree = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): self
    {
        $this->telephone = $telephone;

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
     * @return Collection<int, PayoffSupplier>
     */
    public function getPayoffSupplier(): Collection
    {
        return $this->PayoffSupplier;
    }

    public function addPayoffSupplier(PayoffSupplier $payoffSupplier): self
    {
        if (!$this->PayoffSupplier->contains($payoffSupplier)) {
            $this->PayoffSupplier->add($payoffSupplier);
            $payoffSupplier->setFournisseur($this);
        }

        return $this;
    }

    public function removePayoffSupplier(PayoffSupplier $payoffSupplier): self
    {
        if ($this->PayoffSupplier->removeElement($payoffSupplier)) {
            // set the owning side to null (unless already changed)
            if ($payoffSupplier->getFournisseur() === $this) {
                $payoffSupplier->setFournisseur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, DetteFournisseur>
     */
    public function getDetteFounisseur(): Collection
    {
        return $this->DetteFounisseur;
    }

    public function addDetteFounisseur(DetteFournisseur $detteFounisseur): self
    {
        if (!$this->DetteFounisseur->contains($detteFounisseur)) {
            $this->DetteFounisseur->add($detteFounisseur);
            $detteFounisseur->setFournisseur($this);
        }

        return $this;
    }

    public function removeDetteFounisseur(DetteFournisseur $detteFounisseur): self
    {
        if ($this->DetteFounisseur->removeElement($detteFounisseur)) {
            // set the owning side to null (unless already changed)
            if ($detteFounisseur->getFournisseur() === $this) {
                $detteFounisseur->setFournisseur(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        // TODO: Implement __toString() method.
        return $this->nom;
    }

    /**
     * @return Collection<int, Entree>
     */
    public function getEntree(): Collection
    {
        return $this->Entree;
    }

    public function addEntree(Entree $entree): self
    {
        if (!$this->Entree->contains($entree)) {
            $this->Entree->add($entree);
            $entree->setFournisseur($this);
        }

        return $this;
    }

    public function removeEntree(Entree $entree): self
    {
        if ($this->Entree->removeElement($entree)) {
            // set the owning side to null (unless already changed)
            if ($entree->getFournisseur() === $this) {
                $entree->setFournisseur(null);
            }
        }

        return $this;
    }
}
