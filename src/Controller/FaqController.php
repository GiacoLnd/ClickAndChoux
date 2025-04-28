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
    //Fonction d'affichage du FAQ
    #[Route('/faq', name: 'app_faq')]
    public function index(ProduitRepository $produitRepository, AllergeneRepository $allergeneRepository, CategorieRepository $categorieRepository): Response
    {
        $sale = $categorieRepository->findOneBy(['nomCategorie' => 'Salé']);
        $sucre = $categorieRepository->findOneBy(['nomCategorie' => 'Sucré']);
        $produit = $produitRepository->findAll();
        $allergeneSales = $allergeneRepository->findAllergensByCategory($sale);
        $allergeneSucres = $allergeneRepository->findAllergensByCategory($sucre);
        return $this->render('faq/index.html.twig', [
            'produit' => $produit,
            'allergeneSales' => $allergeneSales,
            'allergeneSucres' => $allergeneSucres,
        ]);
    }
}
