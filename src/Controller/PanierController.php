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
        $forms = [];

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

        // Génération du formulaire pour chaque produit
        foreach ($paniers as $panier) {
            $forms[$panier->getProduit()->getId()] = $this->createForm(PanierType::class, $panier, [
                'action' => $this->generateUrl('panier_update', ['id' => $panier->getProduit()->getId()]),
                'method' => 'POST',
            ])->createView();
        }

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

    // Fonction d'augmentation de la quantité du panier via icone plus
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

    // Fonction de diminution de la quantité du panier via icone moins
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

    // Fonction Validant le panier
    #[Route('/panier/valider', name: 'panier_valider')]
    public function validerPanier(): Response
    {
        // Redirige vers la connexion si non connecté
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }
    
        // Si l'utilisateur est connecté, rediriger directement vers la validation de commande
        return $this->redirectToRoute('commande_confirmer');
    }
    

    // Adaptation de la quantité directement depuis un form dans le panier
    #[Route('/panier/update/{id}', name: 'panier_update', methods: ['POST'])]
    public function updateQuantity(
        Request $request,
        PanierRepository $panierRepository,
        ProduitRepository $produitRepository,
        CommandeRepository $commandeRepository,
        EntityManagerInterface $em,
        int $id
    ): Response {
        $user = $this->getUser();
        
        // Utilisateur connecté
        if ($user) {
            $produit = $produitRepository->find($id);
            $commande = $commandeRepository->findOneBy(['statut' => 'panier', 'user' => $user]);

            if ($produit && $commande) {
                $panier = $panierRepository->findOneBy([
                    'produit' => $produit,
                    'commande' => $commande
                ]);

                if ($panier) {
                    $form = $this->createForm(PanierType::class, $panier);
                    $form->handleRequest($request);

                    if ($form->isSubmitted() && $form->isValid()) {
                        $quantity = $form->get('quantity')->getData();

                        if ($quantity < 1) {
                            $this->addFlash('error', 'Quantité invalide.');
                        } else {
                            $panier->setQuantity($quantity);
                            $em->flush();
                            $this->addFlash('success', 'Quantité mise à jour.');
                        }
                    }
                }
            }
        } else {
            // Utilisateur non connecté : Session
            $session = $request->getSession();
            $cart = $session->get('panier', []);

            if (isset($cart[$id])) {
                $form = $this->createForm(PanierType::class);
                $form->handleRequest($request);

                if ($form->isSubmitted() && $form->isValid()) {
                    $quantity = $form->get('quantity')->getData();

                    if ($quantity < 1) {
                        $this->addFlash('error', 'Quantité invalide.');
                    } else {
                        $cart[$id] = $quantity;
                        $session->set('panier', $cart);
                        $this->addFlash('success', 'Quantité mise à jour.');
                    }
                }
            }
        }

        return $this->redirectToRoute('panier_afficher');
    }

}

