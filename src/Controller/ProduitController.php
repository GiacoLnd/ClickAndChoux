<?php

namespace App\Controller;

use App\Repository\ProduitRepository;
use App\Repository\CategorieRepository;
use Symfony\Component\HttpFoundation\Request;
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

    // Fonction affichant les produits salés
    #[Route('/produit/salty', name: 'salty_produit')]
    public function chouxSales(
        ProduitRepository $produitRepository,
        CategorieRepository $categorieRepository,
        Request $request
    ): Response {
        $categorie = $categorieRepository->findOneBy(['nomCategorie' => 'Salé']);
        $query = $request->query->get('query', '');  // Récupère la valeur du paramètre query dans l'URL
    
         // Si la valeur du paramètre query n'est pas vide, effectue une recherche
        if ($query) {
            $produits = $produitRepository->findBySearchQuery($query, $categorie);
        } else { // Sinon, affiche tous les produits de la catégorie Salé
            $produits = $produitRepository->findBy(['categorie' => $categorie]);
        }
    
        return $this->render('produit/salty.html.twig', [
            'produits' => $produits,
            'query' => $query, 
        ]);
    }

    // Fonction affichant les produits sucrés
    #[Route('/produit/sweety', name: 'sweety_produit')]
    public function chouxSucres(
        ProduitRepository $produitRepository,
        CategorieRepository $categorieRepository,
        Request $request
    ): Response {
        $categorie = $categorieRepository->findOneBy(['nomCategorie' => 'Sucré']);
        $query = $request->query->get('query', '');  // Récupère la valeur du paramètre query dans l'URL
    
        // Si la valeur du paramètre query n'est pas vide, effectue une recherche
        if ($query) {
            $produits = $produitRepository->findBySearchQuery($query, $categorie);
        } else { // Sinon, affiche tous les produits de la catégorie Sucré
            $produits = $produitRepository->findBy(['categorie' => $categorie]);
        }
    
        return $this->render('produit/sweety.html.twig', [
            'produits' => $produits,
            'query' => $query, 
        ]);
    }

    #[Route('/produit/{id}', name: 'produit_detail')]
    public function detail(
        int $id, 
        ProduitRepository $produitRepository
        ): Response {
            
        // Récupère le produit par son ID
        $produit = $produitRepository->find($id);

        // Si le produit n'existe pas, lancer une erreur 404
        if (!$produit) {
            throw $this->createNotFoundException('Produit non trouvé');
        }

        return $this->render('produit/show.html.twig', [
            'produit' => $produit,
        ]);
    }
}

