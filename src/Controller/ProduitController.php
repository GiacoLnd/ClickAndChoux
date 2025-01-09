<?php

namespace App\Controller;

use App\Repository\ProduitRepository;
use App\Repository\CategorieRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProduitController extends AbstractController
{
    #[Route('/produit', name: 'app_produit')]
    public function index(): Response
    {
        return $this->render('produit/index.html.twig', [
            'controller_name' => 'ProduitController',
        ]);
    }

    #[Route('/produit/salty', name: 'salty_produit')]
    public function chouxSales(ProduitRepository $produitRepository, CategorieRepository $categorieRepository): Response
    {
        $categorie = $categorieRepository->findOneBy(['nomCategorie' => 'Salé']);

        $produits = $produitRepository->findBy(['categorie' => $categorie]);

        return $this->render('produit/salty.html.twig', [
            'produits' => $produits,
        ]);
    }
    #[Route('/produit/sweety', name: 'sweety_produit')]
    public function chouxSucrees(ProduitRepository $produitRepository, CategorieRepository $categorieRepository): Response
    {
        $categorie = $categorieRepository->findOneBy(['nomCategorie' => 'Sucré']);

        $produits = $produitRepository->findBy(['categorie' => $categorie]);

        return $this->render('produit/sweety.html.twig', [
            'produits' => $produits,
        ]);
    }
}
