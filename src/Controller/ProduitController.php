<?php

namespace App\Controller;

use App\Entity\Panier;
use App\Entity\Produit;
use App\Entity\Commande;
use App\Form\PanierType;
use App\Entity\Allergene;
use App\Entity\Categorie;
use App\Form\AllergenFilterType;
use App\Controller\PanierController;
use App\Repository\AllergeneRepository;
use App\Repository\PanierRepository;
use App\Repository\ProduitRepository;
use App\Repository\CommandeRepository;
use App\Repository\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
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
        AllergeneRepository $allergeneRepository,
        Request $request,
    ): Response {
        $categorie = $categorieRepository->findOneBy(['nomCategorie' => 'Salé']);
        $query = filter_var($request->query->get('query', ''), FILTER_SANITIZE_FULL_SPECIAL_CHARS);  // filtre les caractères spéciaux (balises et symboles utilisés par Techno) - Faille XSS
        
        $allergenesDisponibles = $allergeneRepository->findAllergensByCategory($categorie); // Utilise la fonction de tri des allergènes par catégorie de produit

        $form = $this->createForm(AllergenFilterType::class, null, [
            'allergenes' => $allergenesDisponibles, // Récupère les allergènes disponibles pour la catégorie
        ]);
        $form->handleRequest($request);
        

        $produits = $produitRepository->findBy(['categorie' => $categorie]);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $selectedAllergens = $data['allergenes'] ?? [];
            
            // Si allergènes sélectionnés
            if (!empty($selectedAllergens)) {
                $selectedAllergenIds = [];
                foreach ($selectedAllergens as $allergene) {
                    $selectedAllergenIds[] = $allergene->getId();
                }
                
                // Filtre les produits sans les allergènes sélectionnés
                $produits = $produitRepository->findByExcludedAllergens($selectedAllergenIds, $categorie);
            }
        }
        
        if (!empty($query)) {
            $produits = $produitRepository->findBySearchQuery($query, $categorie);
        }
    
        return $this->render('produit/salty.html.twig', [
            'produits' => $produits,
            'query' => $query,
            'form' => $form->createView(),
        ]);
    }


    #[Route('/produit/salty/ajax', name: 'ajax_salty_produit', methods: ['GET'])]
    public function ajaxSaltySearch(
        ProduitRepository $produitRepository,
        CategorieRepository $categorieRepository,
        Request $request
    ): JsonResponse {
        $query = filter_var($request->query->get('query', ''), FILTER_SANITIZE_FULL_SPECIAL_CHARS); // filtre les caractères spéciaux (balises et symboles utilisés par Techno) - Faille XSS
        $categorie = $categorieRepository->findOneBy(['nomCategorie' => 'Salé']);

        $results = $produitRepository->findBySearchQuery($query, $categorie);

        $produitsData = [];
        foreach ($results as $produit) {
            $produitsData[] = [ // Filtre des sorties envoyées au JS pour AJAX - Faille XSS - Echappement des sorties
                'id' => filter_var($produit->getId(), FILTER_SANITIZE_NUMBER_INT), // ressort uniquement un entier
                'nomProduit' => htmlspecialchars($produit->getNomProduit(), ENT_QUOTES, 'UTF-8'), // utilisation de htmlspecialchars pour échapper les caractères spéciaux mais garder le nom du produit original (FILTER_SANITIZE_FULL_SPECIAL_CHARS échappant TOUT, aussi les ')
                'image' => htmlspecialchars($produit->getImage()), // idem que nom du produit - nom de l'image échappé, image non affiché correctement
                'getTTC' => filter_var($produit->getTTC(), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION), // ressort un float - autorise le .
            ];
        }

        return new JsonResponse(['produits' => $produitsData]);
    }


    // Fonction affichant les produits sucrés
    #[Route('/produit/sweety', name: 'sweety_produit')]
    public function chouxSucres(
        ProduitRepository $produitRepository,
        CategorieRepository $categorieRepository,
        AllergeneRepository $allergeneRepository,
        Request $request,
    ): Response {
        $categorie = $categorieRepository->findOneBy(['nomCategorie' => 'Sucré']);
        $query = filter_var($request->query->get('query', ''), FILTER_SANITIZE_FULL_SPECIAL_CHARS);  // filtre les caractères spéciaux (balises et symboles utilisés par Techno) - Faille XSS
        
        $allergenesDisponibles = $allergeneRepository->findAllergensByCategory($categorie); // Utilise la fonction de tri des allergènes par catégorie de produit

        $form = $this->createForm(AllergenFilterType::class, null, [
            'allergenes' => $allergenesDisponibles, // Récupère les allergènes disponibles pour la catégorie
        ]); 
        $form->handleRequest($request);
        

        $produits = $produitRepository->findBy(['categorie' => $categorie]);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $selectedAllergens = $data['allergenes'] ?? [];
            
            // Si allergènes sélectionnés
            if (!empty($selectedAllergens)) {
                $selectedAllergenIds = []; // créé un tableau vide
                foreach ($selectedAllergens as $allergene) { 
                    $selectedAllergenIds[] = $allergene->getId(); // insère l'allergène via son id dans le tableau vide
                }
                
                // Filtre les produits sans les allergènes sélectionnés
                $produits = $produitRepository->findByExcludedAllergens($selectedAllergenIds, $categorie);
            }
        }            

        if (!empty($query)) {
            $produits = $produitRepository->findBySearchQuery($query, $categorie);
        }
    
        return $this->render('produit/sweety.html.twig', [
            'produits' => $produits,
            'query' => $query,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/produit/sweety/ajax', name: 'ajax_sweety_produit', methods: ['GET'])]
    public function ajaxSweetySearch(
        ProduitRepository $produitRepository,
        CategorieRepository $categorieRepository,
        Request $request
    ): JsonResponse {
        // Récupère le paramètre 'query' de la requête
        $query = filter_var($request->query->get('query', ''), FILTER_SANITIZE_FULL_SPECIAL_CHARS);  // filtre les caractères spéciaux (balises et symboles utilisés par Techno) - Faille XSS - Echappement des entrées
        $categorie = $categorieRepository->findOneBy(['nomCategorie' => 'Sucré']);

        // Effectue la recherche sur les produits
        $results = $produitRepository->findBySearchQuery($query, $categorie);

        // Prépare les données des produits
        $produitsData = [];
        foreach ($results as $produit) {
            $produitsData[] = [ // Filtre des sorties envoyées au JS pour AJAX - Faille XSS - Echappement des sorties
                'id' => filter_var($produit->getId(), FILTER_SANITIZE_NUMBER_INT), // ressort uniquement un entier
                'nomProduit' => htmlspecialchars($produit->getNomProduit(), ENT_QUOTES, 'UTF-8'), // utilisation de htmlspecialchars pour échapper les caractères spéciaux mais garder le nom du produit original (FILTER_SANITIZE_FULL_SPECIAL_CHARS échappant aussi les ')
                'image' => htmlspecialchars($produit->getImage()), // idem que nom du produit - nom de l'image échappé, image non affiché correctement
                'getTTC' => filter_var($produit->getTTC(), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION), // ressort un float - autorise le .
            ];
        }

        return new JsonResponse(['produits' => $produitsData]);
    }

    // Fonction d'affichage des détails produit
    // Attention : cette fonction gère l'ajout au panier avec une adaptation de la quantité via un input number
    #[Route('/produit/{slug}', name: 'produit_detail', methods: ['GET', 'POST'])]
    public function detailProduit(
        Produit $produit,
        Request $request,
        SessionInterface $session
    ): Response {
        
        $form = $this->createForm(PanierType::class);
        $form->handleRequest($request);
        
        if($produit->isActive() == true) {
            if ($form->isSubmitted() && $form->isValid()) {
                $quantity = (int) $form->get('quantity')->getData();
        
                if ($quantity < 1) {
                    $this->addFlash('danger', 'La quantité doit être supérieure à 0 !');
                } else {
                    // Récupération du panier en session
                    $cart = $session->get('panier', []);
        
                    if (isset($cart[$produit->getId()])) {
                        // Mise à jour quantité
                        $cart[$produit->getId()]['quantite'] += $quantity;
                    } else {
                        // Ajout du produit 
                        $cart[$produit->getId()] = [
                            'id' => $produit->getId(),
                            'nom' => $produit->getNomProduit(),
                            'prixHt' => $produit->getPrixHt(),
                            'TVA' => $produit->getTVA(),
                            'prixTTC' => round($produit->getTTC(), 2),
                            'categorie' => $produit->getCategorie(),
                            'quantite' => $quantity
                        ];
                    }
        
                    // Mise à jour de la session
                    $session->set('panier', $cart);
        
                    $this->addFlash('success', 'Produit ajouté au panier !');
        
                    // Redirection vers la page de catégorie
                    if ($produit->getCategorie() && $produit->getCategorie()->getNomCategorie() === 'Sucré') {
                        return $this->redirectToRoute('sweety_produit');
                    } elseif ($produit->getCategorie() && $produit->getCategorie()->getNomCategorie() === 'Salé') {
                        return $this->redirectToRoute('salty_produit');
                    }
                }
            }
        }
    
        return $this->render('produit/show.html.twig', [
            'produit' => $produit,
            'form' => $form->createView(),
            'allergenes' => $produit->getAllergenes(),
        ]);
    }
    
}