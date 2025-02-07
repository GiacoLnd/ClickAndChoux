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

    if ($user) {
        // Récupère la commande en statut "panier" pour l'utilisateur connecté
        $commande = $commandeRepository->findOneBy(['statut' => 'panier', 'user' => $user]);

        if ($commande) {
            // Récupère le panier liés à cette commande
            $paniers = $panierRepository->findBy(['commande' => $commande]);

            foreach ($paniers as $panier) {
                $montantTotal += $panier->getTotalTTC();
            }
        }
    } else {
        $cart = $session->get('panier', []);
        foreach ($cart as $productId => $quantity) {
            $produit = $produitRepository->find($productId);
            if ($produit) {
                $totalProduit = $produit->getTTC() * $quantity; 
                $montantTotal += $totalProduit; 
                $paniers[] = [
                    'produit' => $produit,
                    'quantity' => $quantity,
                    'montantTotal' => $totalProduit,
                ];
            }
        }
    }

    return $this->render('panier/index.html.twig', [
        'paniers' => $paniers,
        'montantTotal' => $montantTotal
    ]);
}

    #[Route('/panier/add/{id}', name: 'panier_add', methods: ['POST'])]
    public function ajouterAuPanier(
        Produit $produit,
        Request $request,
        SessionInterface $session,
        EntityManagerInterface $em,
        CommandeRepository $commandeRepository,
        PanierRepository $panierRepository
    ): Response {
        $user = $this->getUser();
        $quantity = $request->request->getInt('quantity', 1);

        if (!$user) {
            // Utilisateur non connecté : Gestion via session
            $panier = $session->get('panier', []);

            // Ajouter ou mettre à jour le produit dans le panier
            if (isset($panier[$produit->getId()])) {
                $panier[$produit->getId()] += $quantity;
            } else {
                $panier[$produit->getId()] = $quantity;
            }

            $session->set('panier', $panier);

            $this->addFlash('success', 'Produit ajouté au panier (session).');
        } else {
            // Utilisateur connecté : Gestion via base de données
            $commande = $commandeRepository->findOneBy(['statut' => 'panier', 'user' => $user]);

            if (!$commande) {
                $commande = new Commande();
                $commande->setStatut('panier');
                $commande->setDateCommande(new \DateTime());
                $commande->setUser($user);
                $em->persist($commande);
                $em->flush();
            }

            $existingPanier = $panierRepository->findOneBy(['produit' => $produit, 'commande' => $commande]);

            if ($existingPanier) {
                // Mettre à jour la quantité
                $existingPanier->setQuantity($existingPanier->getQuantity() + $quantity);
            } else {
                // Créer une nouvelle ligne dans le panier
                $panier = new Panier();
                $panier->setProduit($produit);
                $panier->setQuantity($quantity);
                $panier->setCommande($commande);
                $em->persist($panier);
            }

            $em->flush();

            $this->addFlash('success', 'Produit ajouté au panier (base de données).');
        }

        return $this->redirectToRoute('panier_afficher');
    }

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


    #[Route('/panier/increase/{id}', name: 'panier_increase', methods: ['POST'])]
    public function augmenterQuantite(
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
                    $panier->setQuantity($panier->getQuantity() + 1);
                    $em->flush();
                    $this->addFlash('success', 'Quantité augmentée (base de données).');
                }
            }
        } else {
            // Utilisateur non connecté : gestion via session
            $cart = $session->get('panier', []);
            $productId = $produit->getId();

            if (isset($cart[$productId])) {
                $cart[$productId]++;
                $session->set('panier', $cart);
                $this->addFlash('success', 'Quantité augmentée (session).');
            }
        }

        return $this->redirectToRoute('panier_afficher');
    }

    #[Route('/panier/decrease/{id}', name: 'panier_decrease', methods: ['POST'])]
    public function diminuerQuantite(
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

                if ($panier && $panier->getQuantity() > 1) {
                    $panier->setQuantity($panier->getQuantity() - 1);
                    $em->flush();
                    $this->addFlash('success', 'Quantité diminuée (base de données).');
                } elseif ($panier && $panier->getQuantity() === 1) {
                    $em->remove($panier);
                    $em->flush();
                    $this->addFlash('success', 'Produit supprimé du panier (base de données).');
                }
            }
        } else {
            // Utilisateur non connecté : gestion via session
            $cart = $session->get('panier', []);
            $productId = $produit->getId();

            if (isset($cart[$productId])) {
                if ($cart[$productId] > 1) {
                    $cart[$productId]--;
                } else {
                    unset($cart[$productId]);
                }
                $session->set('panier', $cart);
                $this->addFlash('success', 'Quantité diminuée (session).');
            }
        }

        return $this->redirectToRoute('panier_afficher');
    }


    
    #[Route('/panier/valider', name: 'panier_valider')]
    public function validerPanier(SessionInterface $session, TokenStorageInterface $tokenStorage): Response
    {
        $user = $tokenStorage->getToken()?->getUser();
    
        if (!$user || !is_object($user)) {
            $this->addFlash('warning', 'Veuillez vous connecter pour valider votre panier.');
    
            // ✅ Stocke la redirection en session
            $session->set('redirect_after_login', $this->generateUrl('panier_afficher'));
    
            return $this->redirectToRoute('app_login');
        }
    
        return $this->redirectToRoute('commande_valider');
    }
    
    
    
    
    


}

