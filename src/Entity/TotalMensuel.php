<?php

namespace App\Entity;

use App\Repository\TotalMensuelRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TotalMensuelRepository::class)]
class TotalMensuel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $totalMonth = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $totalYear = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $totalThreeMonth = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private $dateCalcul;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTotalMonth(): ?string
    {
        return $this->totalMonth;
    }

    public function setTotalMonth(?string $totalMonth): self
    {
        $this->totalMonth = $totalMonth;

        return $this;
    }

    public function getTotalYear(): ?string
    {
        return $this->totalYear;
    }

    public function setTotalYear(?string $totalYear): self
    {
        $this->totalYear = $totalYear;

        return $this;
    }

    public function getTotalThreeMonth(): ?string
    {
        return $this->totalThreeMonth;
    }

    public function setTotalThreeMonth(?string $totalThreeMonth): self
    {
        $this->totalThreeMonth = $totalThreeMonth;

        return $this;
    }

    // Getter et setter pour la date de calcul
    public function getDateCalcul(): ?\DateTimeInterface
    {
        return $this->dateCalcul;
    }

    public function setDateCalcul(\DateTimeInterface $dateCalcul): self
    {
        $this->dateCalcul = $dateCalcul;

        return $this;
    }
}
