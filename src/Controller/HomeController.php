<?php

namespace App\Controller;

use App\Repository\PanierRepository;
use App\Repository\ProduitRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ProduitRepository $produitRepository, PanierRepository $panierRepository): Response
    {
        $rawBestSellerIds = $panierRepository->findBestSellerProductIds();

        // 2. Extraction uniquement des IDs depuis le tableau
        $ids = array_column($rawBestSellerIds, 'produitId');
    
        // 3. Récupération des entités Produit correspondantes
        $bestSellers = $produitRepository->findBy(['id' => $ids]);

        return $this->render('home/index.html.twig', [
            'bestSellers' => $bestSellers,
        ]);
    }
}
 