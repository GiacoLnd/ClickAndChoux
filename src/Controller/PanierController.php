<?php

namespace App\Controller;

use App\Entity\Panier;
use App\Entity\Produit;
use App\Entity\Commande;
use App\Form\PanierType;
use App\Repository\PanierRepository;
use App\Repository\ProduitRepository;
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

class PanierController extends AbstractController
{
    // Fonction d'affichage du panier
    #[Route('/panier', name: 'panier_afficher')]
    public function afficherPanier(SessionInterface $session, ProduitRepository $produitRepository): Response
    {
        $cart = $session->get('panier', []);
        $montantTotal = 0;
        $cartQuantity = 0;
        $paniers = [];
    
        foreach ($cart as $item) {
            // Récupération du produit depuis la base de données pour obtenir son slug
            $produit = $produitRepository->find($item['id']);
    
            if ($produit) {
                $paniers[] = [
                    'id' => $item['id'],
                    'nom' => $item['nom'],
                    'prixTTC' => $item['prixTTC'],
                    'quantite' => $item['quantite'],
                    'total' => $item['prixTTC'] * $item['quantite'],
                    'slug' => $produit->getSlug(), // Ajout du slug récupéré depuis la base
                    'image' => $produit->getImage() // Ajout de l'image si elle est stockée en base
                ];
    
                // Mise à jour du montant total et de la quantité totale du panier
                $montantTotal += $item['prixTTC'] * $item['quantite'];
                $cartQuantity += $item['quantite'];
            }
        }
    
        return $this->render('panier/index.html.twig', [
            'paniers' => $paniers,
            'montantTotal' => $montantTotal,
            'cartQuantity' => $cartQuantity
        ]);
    }
    
    // Fonction de suppression d'un produit du panier
    #[Route('/panier/remove/{id}', name: 'panier_remove', methods: ['POST'])]
    public function supprimerProduit(
        int $id,
        SessionInterface $session
    ): Response {
        // Récupération du panier en session
        $cart = $session->get('panier', []);

        if (isset($cart[$id])) {
            unset($cart[$id]); // Suppression du produit en session
            $session->set('panier', $cart);
            $this->addFlash('success', 'Produit supprimé du panier');
        } else {
            $this->addFlash('warning', 'Produit non trouvé dans le panier');
        }

        return $this->redirectToRoute('panier_afficher');
    }

    // Fonction de suppression de tous les produits du panier
    #[Route('/panier/clear', name: 'panier_clear', methods: ['POST'])]
    public function supprimerToutPanier(SessionInterface $session): Response
    {
        // Suppression totale du panier en session
        $session->remove('panier');
        
        $this->addFlash('success', 'Tous les produits ont été supprimés du panier !');
    
        $response = new RedirectResponse($this->generateUrl('panier_afficher'));
        $response->headers->clearCookie('panier_backup');
    
        return $response;
    }

    // Fonction Validant le panier
    #[Route('/panier/valider', name: 'panier_valider', methods: ['GET', 'POST'])]
    public function validerPanier(SessionInterface $session): Response
    {
        if (!$this->getUser()) {
            $this->addFlash('warning', 'Vous devez être connecté pour valider votre panier.');
            return $this->redirectToRoute('app_login');
        }
    
        $cart = $session->get('panier', []);
        if (empty($cart)) {
            $this->addFlash('warning', 'Votre panier est vide.');
            return $this->redirectToRoute('panier_afficher');
        }
    
        // Redirection vers la confirmation de commande
        return $this->redirectToRoute('commande_confirmer');
    }
    
    

    // Fonction AJAX d'augmentation de la quantité en panier 
    #[Route('/panier/increase-ajax/{id}', name: 'panier_increase_ajax', methods: ['POST'])]
    public function augmenterQuantiteAjax(int $id, SessionInterface $session, ProduitRepository $produitRepository): JsonResponse
    {
        // Structure de base pour JSON
        $response = [
            'success'      => false,
            'message'      => '',
            'newQuantity'  => 0,
            'newTotal'     => 0,
            'cartQuantity' => 0,
        ];
    
        // Récupération du panier
        $cart = $session->get('panier', []);
    
        // Vérification si produit existe en base de données
        $produit = $produitRepository->find($id);
        if (!$produit) {
            $response['message'] = 'Produit non trouvé.';
            return new JsonResponse($response);
        }
    
        // Augmentation de quantité
        if (isset($cart[$id])) {
            $cart[$id]['quantite']++;
        }
    
        // Mise à jour de la session avec panier modifié
        $session->set('panier', $cart);
    
        // Recalcul du total global et du nombre d'articles
        $newTotal = 0;
        $cartQuantity = 0;
        foreach ($cart as $item) {
            $newTotal += $item['prixTTC'] * $item['quantite'];
            $cartQuantity += $item['quantite'];
        }
    
        // Mise à jour de réponse JSON
        $response['success'] = true;
        $response['newTotal'] = $newTotal;
        $response['cartQuantity'] = $cartQuantity;
        $response['newQuantity'] = $cart[$id]['quantite'];
    
        return new JsonResponse($response);
    }
    
    
    // Fonction AJAX de diminution de la quantité en panier 
    #[Route('/panier/decrease-ajax/{id}', name: 'panier_decrease_ajax', methods: ['POST'])]
    public function diminuerQuantiteAjax(int $id, SessionInterface $session, ProduitRepository $produitRepository): JsonResponse
    {
        $response = [
            'success'      => false,
            'message'      => '',
            'newQuantity'  => 0,
            'newTotal'     => 0,
            'cartQuantity' => 0,
        ];

        // Récupération du panier
        $cart = $session->get('panier', []);

        // Vérification si produit existe en base de données
        $produit = $produitRepository->find($id);
        if (!$produit) {
            $response['message'] = 'Produit non trouvé.';
            return new JsonResponse($response);
        }

        // Diminution de quantité
        if (isset($cart[$id])) {
            if ($cart[$id]['quantite'] > 1) {
                $cart[$id]['quantite']--;
            }
        }

        // Mise à jour du panier
        $session->set('panier', $cart);

        // Recalcul du total global et du nombre d'articles
        $newTotal = 0;
        $cartQuantity = 0;
        foreach ($cart as $item) {
            $newTotal += $item['prixTTC'] * $item['quantite'];
            $cartQuantity += $item['quantite'];
        }

        // Mise à jour de la réponse JSON
        $response['success'] = true;
        $response['newTotal'] = $newTotal;
        $response['cartQuantity'] = $cartQuantity;
        $response['newQuantity'] = $cart[$id]['quantite'] ?? 0; // Si supprimé, retourne 0

        return new JsonResponse($response);
    }

    
}
