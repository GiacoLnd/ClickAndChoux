<?php

namespace App\Controller;

use App\Entity\Panier;
use App\Entity\Produit;
use App\Entity\Commande;
use App\Form\PanierType;
use App\Controller\PanierController;
use App\Repository\PanierRepository;
use App\Repository\ProduitRepository;
use App\Repository\CommandeRepository;
use App\Repository\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
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

    #[Route('/produit/{id}', name: 'produit_detail', methods: ['GET', 'POST'])]
    public function detailProduit(
        Produit $produit,
        Request $request,
        SessionInterface $session,
        EntityManagerInterface $em,
        PanierRepository $panierRepository,
        CommandeRepository $commandeRepository
    ): Response {
        
        $user = $this->getUser();
    
        // Créer un formulaire d'ajout au panier
        $panier = new Panier();
        $form = $this->createFormBuilder($panier)
        ->add('quantity', IntegerType::class, [
            'label' => 'Quantité',
            'attr' => [
                'min' => 1,
                'value' => 1,
                'class' => 'quantity',
            ],
        ])
        ->add('ajouter', SubmitType::class, [
            'label' => 'Ajouter au panier',
            'attr' => [
                'class' => 'add-to-cart-button', 
            ],
        ])
            ->getForm();
    
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Récupération de la quantité en s'assurant que c'est bien un entier
            $quantity = (int) $form->get('quantity')->getData();
    
            if ($user) {
                // Utilisateur connecté : gestion via la base de données
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
                    $existingPanier->setQuantity($existingPanier->getQuantity() + $quantity);
                } else {
                    $panier->setProduit($produit);
                    $panier->setQuantity($quantity);
                    $panier->setCommande($commande);
                    $em->persist($panier);
                }
    
                $em->flush();
            } else {
                // Utilisateur non connecté : gestion via session
                $cart = $session->get('panier', []);
                $productId = $produit->getId();
    
                // S'assurer que l'élément existant dans $cart[$productId] est un entier
                if (isset($cart[$productId])) {
                    $cart[$productId] = (int) $cart[$productId] + $quantity;
                } else {
                    $cart[$productId] = $quantity;
                }
    
                // Mettre à jour la session avec le panier corrigé
                $session->set('panier', $cart);
            }
    
            $this->addFlash('success', 'Produit ajouté au panier !');
            return $this->redirectToRoute('panier_afficher');
        }
    
        return $this->render('produit/show.html.twig', [
            'produit' => $produit,
            'form' => $form->createView(),
        ]);
    }
    
    
}

