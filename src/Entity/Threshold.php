<?php

namespace App\Entity;

use App\Repository\ThresholdRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @codeCoverageIgnore
 */
#[ORM\Entity(repositoryClass: ThresholdRepository::class)]
class Threshold
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 3)]
    private ?string $currency = null;

    #[ORM\Column]
    private ?float $level = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getLevel(): ?float
    {
        return $this->level;
    }

    public function setLevel(float $level): static
    {
        $this->level = $level;

        return $this;
    }
}
