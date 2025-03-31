<?php

namespace App\Entity;

use App\Repository\PanierRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PanierRepository::class)]

class Panier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $quantity = null;

    #[ORM\ManyToOne(inversedBy: 'paniers')]
    #[ORM\JoinColumn(nullable: true, onDelete: "CASCADE")]
    private ?Produit $produit = null;

    #[ORM\ManyToOne(targetEntity: Commande::class, inversedBy: 'paniers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Commande $commande = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getProduit(): ?Produit
    {
        return $this->produit;
    }

    public function setProduit(?Produit $produit): static
    {
        $this->produit = $produit; // Correction du nom de la propriété
        return $this;
    }

    public function getCommande(): ?Commande
    {
        return $this->commande; // Correction du nom de la propriété
    }

    public function setCommande(?Commande $commande): static
    {
        $this->commande = $commande; // Correction du nom de la propriété
        return $this;
    }

    public function getTotalTTC(): float
    {
        if ($this->produit === null) {
            return 0.0;
        }

        return $this->produit->getTTC() * $this->quantity;
    }
}
