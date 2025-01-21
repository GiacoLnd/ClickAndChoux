<?php

namespace App\Controller;

use App\Entity\Panier;
use App\Entity\Produit;
use App\Entity\Commande;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PanierController extends AbstractController
{
    #[Route('/panier', name: 'panier')]
    public function index(SessionInterface $session, EntityManagerInterface $em): Response
    {
        $panier = [];
        $total = 0;
    
        if ($this->getUser()) {
            // Utilisateur connecté : récupérer les produits depuis la base
            $commande = $em->getRepository(Commande::class)->findOneBy([
                'statut' => 'panier',
                'user' => $this->getUser(),
            ]);
    
            if ($commande) {
                foreach ($commande->getPaniers() as $item) {
                    $panier[] = [
                        'produit' => $item->getProduit(),
                        'quantity' => $item->getQuantity(),
                        'price' => $item->getProduit()->getTTC(),
                    ];
                    $total += $item->getProduit()->getTTC() * $item->getQuantity();
                }
            }
        } else {
            // Utilisateur non connecté : récupérer le panier depuis la session
            $panier = $session->get('panier', []);
            foreach ($panier as $item) {
                $total += $item['price'] * $item['quantity'];
            }
        }
    
        return $this->render('panier/index.html.twig', [
            'panier' => $panier,
            'total' => $total,
        ]);
    }
    
    #[Route('/panier/add/{id}', name: 'panier_add')]
    public function add(Produit $produit, Request $request, SessionInterface $session, EntityManagerInterface $em): Response
    {
        // Récupérer la quantité depuis le formulaire ou une valeur par défaut
        $quantity = (int) $request->request->get('quantity', 1);
        var_dump($request->request->all());
        die;
        if ($quantity <= 0) {
            $this->addFlash('error', 'La quantité doit être supérieure à zéro.');
            return $this->redirectToRoute('produit_detail', ['id' => $produit->getId()]);
        }
    
        if ($this->getUser()) {
            // Gestion pour un utilisateur connecté
            $commande = $em->getRepository(Commande::class)->findOneBy([
                'statut' => 'panier',
                'user' => $this->getUser(),
            ]);
    
            if (!$commande) {
                $commande = new Commande();
                $commande->setStatut('panier');
                $commande->setDate(new \DateTime());
                $commande->setUser($this->getUser());
                $em->persist($commande);
            }
    
            // Vérifier si le produit est déjà dans le panier
            $panierItem = $em->getRepository(Panier::class)->findOneBy([
                'produit' => $produit,
                'commande' => $commande,
            ]);
    
            if ($panierItem) {
                // Mettre à jour la quantité
                $panierItem->setQuantity($panierItem->getQuantity() + $quantity);
            } else {
                // Créer une nouvelle ligne de panier
                $panierItem = new Panier();
                $panierItem->setProduit($produit);
                $panierItem->setCommande($commande);
                $panierItem->setQuantity($quantity);
                $em->persist($panierItem);
            }
    
            $em->flush();
            $this->addFlash('success', 'Produit ajouté au panier.');
        } else {
            // Gestion pour un utilisateur non connecté (via la session)
            $panier = $session->get('panier', []);
    
            if (isset($panier[$produit->getId()])) {
                $panier[$produit->getId()]['quantity'] += $quantity;
            } else {
                $panier[$produit->getId()] = [
                    'id' => $produit->getId(),
                    'name' => $produit->getNomProduit(),
                    'price' => $produit->getTTC(),
                    'quantity' => $quantity,
                ];
            }
    
            $session->set('panier', $panier);
            $this->addFlash('success', 'Produit ajouté au panier.');
        }
    
        return $this->redirectToRoute('produit_detail', ['id' => $produit->getId()]);
    }
    #[Route('/panier/valider', name: 'panier_valider')]
public function valider(SessionInterface $session, EntityManagerInterface $em): Response
{
    if ($this->getUser()) {
        // Cas utilisateur connecté
        $commande = new Commande();
        $commande->setStatut('validée');
        $commande->setDate(new \DateTime());
        $commande->setUser($this->getUser());

        // Récupérer les produits du panier (en base)
        $panierItems = $em->getRepository(Panier::class)->findBy([
            'commande' => $em->getRepository(Commande::class)->findOneBy([
                'statut' => 'panier',
                'user' => $this->getUser(),
            ]),
        ]);

        if (!$panierItems) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('panier');
        }

        // Transférer les produits du panier vers la nouvelle commande
        foreach ($panierItems as $item) {
            $item->setCommande($commande);
            $em->persist($item);
        }

        // Sauvegarder la commande
        $em->persist($commande);
        $em->flush();

        $this->addFlash('success', 'Votre commande a été validée.');
    } else {
        // Cas utilisateur non connecté
        $panierSession = $session->get('panier', []);

        if (empty($panierSession)) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('panier');
        }

        // Créer une nouvelle commande
        $commande = new Commande();
        $commande->setStatut('validée');
        $commande->setDate(new \DateTime());
        $em->persist($commande);

        // Transférer les données de la session vers la commande
        foreach ($panierSession as $item) {
            $produit = $em->getRepository(Produit::class)->find($item['id']);

            if ($produit) {
                $panierItem = new Panier();
                $panierItem->setProduit($produit);
                $panierItem->setQuantit($item['quantity']);
                $panierItem->setCommande($commande);
                $em->persist($panierItem);
            }
        }

        // Sauvegarder la commande
        $em->flush();

        // Vider le panier en session
        $session->remove('panier');

        $this->addFlash('success', 'Votre commande a été validée.');
    }

    return $this->redirectToRoute('commande_detail', ['id' => $commande->getId()]);
}

    #[Route('/panier/remove/{id}', name: 'panier_remove')]
    public function remove(Produit $produit, SessionInterface $session, EntityManagerInterface $em): Response
    {
        if ($this->getUser()) {
            // Cas utilisateur connecté : supprimer depuis la base
            $commande = $em->getRepository(Commande::class)->findOneBy([
                'statut' => 'panier',
                'user' => $this->getUser(),
            ]);

            if ($commande) {
                // Chercher l'élément à supprimer dans le panier
                $panierItem = $em->getRepository(Panier::class)->findOneBy([
                    'produit' => $produit,
                    'commande' => $commande,
                ]);

                if ($panierItem) {

                    $em->remove($panierItem);
                    $em->flush();
                    $this->addFlash('success', 'Produit supprimé du panier.');
                } else {
                    $this->addFlash('error', 'Le produit n’est pas présent dans votre panier.');
                }
            } else {
                $this->addFlash('error', 'Aucune commande en cours.');
            }
        } else {
            // Cas utilisateur non connecté : supprimer depuis la session
            $panier = $session->get('panier', []);

            if (isset($panier[$produit->getId()])) {
                unset($panier[$produit->getId()]); // Supprimer le produit de la session
                $session->set('panier', $panier); // Mettre à jour le panier en session
                $this->addFlash('success', 'Produit supprimé du panier.');
            } else {
                $this->addFlash('error', 'Le produit n’est pas présent dans votre panier.');
            }
        }

        return $this->redirectToRoute('panier'); // Rediriger vers la page du panier
    }

    #[Route('/panier/clear', name: 'panier_clear')]
    public function clear(SessionInterface $session, EntityManagerInterface $em): Response
    {
        if ($this->getUser()) {
            // Cas utilisateur connecté : supprimer tous les éléments du panier en base
            $commande = $em->getRepository(Commande::class)->findOneBy([
                'statut' => 'panier',
                'user' => $this->getUser(),
            ]);

            if ($commande) {
                foreach ($commande->getPaniers() as $panierItem) {
                    $em->remove($panierItem);
                }

                $em->flush(); // Sauvegarder la suppression
                $this->addFlash('success', 'Votre panier a été vidé.');
            } else {
                $this->addFlash('error', 'Aucune commande en cours à vider.');
            }
        } else {
            // Cas utilisateur non connecté : vider le panier en session
            $session->remove('panier');
            $this->addFlash('success', 'Votre panier a été vidé.');
        }

        return $this->redirectToRoute('panier'); // Rediriger vers la page du panier
    }


}

