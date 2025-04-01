<?php

namespace App\Controller;

use App\Repository\AllergeneRepository;
use App\Repository\CategorieRepository;
use App\Repository\ProduitRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FaqController extends AbstractController
{
    #[Route('/faq', name: 'app_faq')]
    public function index(ProduitRepository $produitRepository, AllergeneRepository $allergeneRepository, CategorieRepository $categorieRepository): Response
    {
        $salé = $categorieRepository->findOneBy(['nomCategorie' => 'Salé']);
        $sucré = $categorieRepository->findOneBy(['nomCategorie' => 'Sucré']);
        $produit = $produitRepository->findAll();
        $allergeneSalés = $allergeneRepository->findAllergensByCategory($salé);
        $allergeneSucrés = $allergeneRepository->findAllergensByCategory($sucré);
        return $this->render('faq/index.html.twig', [
            'produit' => $produit,
            'allergeneSalés' => $allergeneSalés,
            'allergeneSucrés' => $allergeneSucrés,
        ]);
    }
}
