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
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

class PanierController extends AbstractController
{
    #[Route('/panier', name: 'panier_afficher')]
    public function afficherPanier(
        SessionInterface $session,
        PanierRepository $panierRepository,
        ProduitRepository $produitRepository,
        CommandeRepository $commandeRepository
    ): Response {
        $user = $this->getUser();
        $paniers = [];
        $montantTotal = 0;
        $forms = [];
        $response['cartQuantity'] = 0;

        // Récupération des produits du panier
        if ($user) {
            $commande = $commandeRepository->findOneBy(['statut' => 'panier', 'user' => $user]);

            if ($commande) {
                $paniers = $panierRepository->findBy(['commande' => $commande]);
            }
        } else {
            $cart = $session->get('panier', []);
            foreach ($cart as $productId => $quantity) {
                $produit = $produitRepository->find($productId);
                if ($produit) {
                    $panier = new Panier();
                    $panier->setProduit($produit);
                    $panier->setQuantity($quantity);
                    $paniers[] = $panier;
                }
            }
        }

        // Calcul du montant total
        foreach ($paniers as $panier) {
            $montantTotal += $panier->getProduit()->getTTC() * $panier->getQuantity();
        }

        // Génération du formulaire pour chaque produi

        return $this->render('panier/index.html.twig', [
            'paniers' => $paniers,
            'montantTotal' => $montantTotal,
            'forms' => $forms,
        ]);
    }

    
    // Fonction de suppression d'un produit du panier
    #[Route('/panier/remove/{id}', name: 'panier_remove', methods: ['POST'])]
    public function supprimerProduit(
        Produit $produit,
        SessionInterface $session,
        EntityManagerInterface $em,
        CommandeRepository $commandeRepository,
        PanierRepository $panierRepository
    ): Response {
        $user = $this->getUser();

        if ($user) {
            // Utilisateur connecté : gestion via la base de données
            $commande = $commandeRepository->findOneBy(['statut' => 'panier', 'user' => $user]);

            if ($commande) {
                $panier = $panierRepository->findOneBy(['produit' => $produit, 'commande' => $commande]);

                if ($panier) {
                    $em->remove($panier);
                    $em->flush();
                    $this->addFlash('success', 'Produit supprimé du panier (base de données).');
                } else {
                    $this->addFlash('warning', 'Produit non trouvé dans le panier.');
                }
            }
        } else {
            // Utilisateur non connecté : gestion via session
            $cart = $session->get('panier', []);

            if (isset($cart[$produit->getId()])) {
                unset($cart[$produit->getId()]);
                $session->set('panier', $cart);
                $this->addFlash('success', 'Produit supprimé du panier (session).');
            } else {
                $this->addFlash('warning', 'Produit non trouvé dans le panier.');
            }
        }

    return $this->redirectToRoute('panier_afficher');
    }

    // Fonction de suppression de tous les produits du panier
    #[Route('/panier/clear', name: 'panier_clear', methods: ['POST'])]
    public function supprimerToutPanier(
        SessionInterface $session,
        EntityManagerInterface $em,
        CommandeRepository $commandeRepository,
        PanierRepository $panierRepository
    ): Response {
        $user = $this->getUser();

        if ($user) {
            // Utilisateur connecté : gestion via la base de données
            $commande = $commandeRepository->findOneBy(['statut' => 'panier', 'user' => $user]);

            if ($commande) {
                $paniers = $panierRepository->findBy(['commande' => $commande]);

                foreach ($paniers as $panier) {
                    $em->remove($panier);
                }

                $em->flush();

                $this->addFlash('success', 'Tous les produits ont été supprimés du panier (base de données).');
            } else {
                $this->addFlash('warning', 'Aucun panier trouvé.');
            }
        } else {
            // Utilisateur non connecté : gestion via session
            $session->remove('panier');
            $this->addFlash('success', 'Tous les produits ont été supprimés du panier (session).');
        }

        return $this->redirectToRoute('panier_afficher');
    }

    // Fonction Validant le panier
    #[Route('/panier/valider', name: 'panier_valider')]
    public function validerPanier(): Response
    {
        // Redirige vers la connexion si non connecté
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }
    
        // Si l'utilisateur est connecté, redirige directement vers la validation de commande
        return $this->redirectToRoute('commande_confirmer');
    }
    

    // Fonction AJAX d'augmentation de la quantité en panier 
    #[Route('/panier/increase-ajax/{id}', name: 'panier_increase_ajax', methods: ['POST'])]
    public function augmenterQuantiteAjax(
        Produit $produit,
        SessionInterface $session,
        EntityManagerInterface $em,
        CommandeRepository $commandeRepository,
        PanierRepository $panierRepository
    ): JsonResponse {
        $user = $this->getUser();
        // Structure de base pour la réponse JSON
        $response = [
            'success'      => false,
            'message'      => '',
            'newQuantity'  => 0,
            'newTotal'     => 0,
            'cartQuantity' => 0,
        ];
    
        // Cas 1 : Utilisateur connecté
        if ($user) {
            // Récupération de la commande 'panier' de l'utilisateur
            $commande = $commandeRepository->findOneBy(['statut' => 'panier', 'user' => $user]);
            if ($commande) {
                $panier = $panierRepository->findOneBy(['produit' => $produit, 'commande' => $commande]);
                if ($panier) {
                    $panier->setQuantity($panier->getQuantity() + 1);
                    $em->flush();
                }
                // Recalcul du total global
                $paniers = $panierRepository->findBy(['commande' => $commande]);
                $newTotal = 0;
                $cartQuantity = 0;
                foreach ($paniers as $p) {
                    $newTotal += $p->getProduit()->getTTC() * $p->getQuantity();
                    $cartQuantity += $p->getQuantity();
                }
    
                $response['success']      = true;
                $response['newTotal']     = $newTotal;
                $response['cartQuantity'] = $cartQuantity;
                $response['newQuantity']  = $panier ? $panier->getQuantity() : 0;
            }
        } 
        // Cas 2 : Utilisateur non connecté
        else {
            $cart = $session->get('panier', []);
            $productId = $produit->getId();
    
            if (isset($cart[$productId])) {
                $cart[$productId]++; 
            } else {
                $cart[$productId] = 1;
            }
            $session->set('panier', $cart);
    
            // Recalcul du total et la quantité globale
            $newTotal = 0;
            $cartQuantity = 0;
            foreach ($cart as $prodId => $qty) {
                $prod = $em->getRepository(Produit::class)->find($prodId); 
                if ($prod) {
                    $newTotal += $prod->getTTC() * $qty;
                    $cartQuantity += $qty;
                }
            }
    
            $response['success']      = true;
            $response['newTotal']     = $newTotal;
            $response['cartQuantity'] = $cartQuantity;
            $response['newQuantity']  = $cart[$productId] ?? 0;
        }
    
        return new JsonResponse($response);
    }
    
    // Fonction AJAX de diminution de la quantité en panier 
    #[Route('/panier/decrease-ajax/{id}', name: 'panier_decrease_ajax', methods: ['POST'])]
    public function diminuerQuantiteAjax(
        Produit $produit,
        SessionInterface $session,
        EntityManagerInterface $em,
        CommandeRepository $commandeRepository,
        PanierRepository $panierRepository
    ): JsonResponse {
        $user = $this->getUser();
    
        $response = [
            'success'      => false,
            'message'      => '',
            'newQuantity'  => 0,
            'newTotal'     => 0,
            'cartQuantity' => 0,
        ];
    
        // Cas 1 : Utilisateur connecté 
        if ($user) {
            $commande = $commandeRepository->findOneBy(['statut' => 'panier', 'user' => $user]);
            if ($commande) {
                $panier = $panierRepository->findOneBy(['produit' => $produit, 'commande' => $commande]);
                if ($panier) {
                    // Interdiction de passer sous 1 - suppression gérée par route panier_remove
                    if ($panier->getQuantity() > 1) {
                        $panier->setQuantity($panier->getQuantity() - 1);
                        $em->flush();
                    }
                }
                // Recalcul du total et la quantité du panier
                $paniers = $panierRepository->findBy(['commande' => $commande]);
                $newTotal = 0;
                $cartQuantity = 0;
                foreach ($paniers as $p) {
                    $newTotal += $p->getProduit()->getTTC() * $p->getQuantity();
                    $cartQuantity += $p->getQuantity();
                }
    
                $response['success']      = true;
                $response['newTotal']     = $newTotal;
                $response['cartQuantity'] = $cartQuantity;
                $response['newQuantity']  = $panier ? $panier->getQuantity() : 0;
            }
        }
        // Cas 2 : Utilisateur non connecté
        else {
            $cart = $session->get('panier', []);
            $productId = $produit->getId();
    
            if (isset($cart[$productId])) {
                // Empêche de passer en dessous de 1
                if ($cart[$productId] > 1) {
                    $cart[$productId]--;
                }
                $session->set('panier', $cart);
            }
    
            // Recalcul du total et de la quantité globale
            $newTotal = 0;
            $cartQuantity = 0;
            foreach ($cart as $prodId => $qty) {
                $prod = $em->getRepository(Produit::class)->find($prodId);
                if ($prod) {
                    $newTotal += $prod->getTTC() * $qty;
                    $cartQuantity += $qty;
                }
            }
    
            $response['success']      = true;
            $response['newTotal']     = $newTotal;
            $response['cartQuantity'] = $cartQuantity;
            $response['newQuantity']  = $cart[$productId] ?? 0;
        }
    
        return new JsonResponse($response);
    }
    
}
