<?php

namespace App\Controller;

use DateTime;
use App\Entity\Produit;
use App\Form\PanierType;
use App\Entity\Commentaire;
use App\Form\CommentaireType;
use Doctrine\ORM\EntityManager;
use App\Form\AllergenFilterType;
use App\Form\UpdateCommentaireType;
use App\Repository\ProduitRepository;
use App\Repository\CommandeRepository;
use App\Repository\AllergeneRepository;
use App\Repository\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\CommentaireRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
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
                'slug' => htmlspecialchars($produit->getSlug())
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
                'slug' => htmlspecialchars($produit->getSlug())
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
        SessionInterface $session,
        EntityManagerInterface $em,
        CommandeRepository $commandeRepository,
        CommentaireRepository $commentaireRepository,
    ): Response {
        $user = $this->getUser();
        $hasOrdered = false; // Initialise en false de base

        if($user) {
            $commandes = $commandeRepository->findBy(['user' => $user]);

            foreach ($commandes as $commande) {
                // Vérification de la validation du paiement
                if ($commande->getStatutPaiement() === 'payé') {
                    // Recherche des paniers associés à cette commande
                    $paniers = $commande->getPaniers();

                    foreach ($paniers as $panier) {
                        if ($panier->getProduit() === $produit) {
                            // Si produit commandé par user -> passe en true
                            $hasOrdered = true;
                            break 2; // Sort de 2 boucles quand produit commandé = OK
                        }
                    }
                }
            }
        }

        $commentaire = new Commentaire();
        $commentForm = $this->createForm(CommentaireType::class, $commentaire);

        if ($hasOrdered) {
            $commentForm->handleRequest($request);

            if ($commentForm->isSubmitted() && $commentForm->isValid()) {
                $commentaire = $commentForm->getData();
                $commentaire->setUser($user);
                $commentaire->setProduit($produit);
                $commentaire->setDateCommentaire(new datetime());

                $em->persist($commentaire);
                $em->flush();

                $this->addFlash('success', 'Votre commentaire a été ajouté.');

                return $this->redirectToRoute('produit_detail', ['slug' => $produit->getSlug()]);
            }
        }

        $limit = 5;
        $commentaires = $commentaireRepository->findCommentairesByProduit($produit, 1, $limit);  // Page 1 avec un maximum de 5 commentaires
    
        // Vérification du nombre total de commentaires pour le produit
        $totalCommentaires = $commentaireRepository->countCommentairesByProduit($produit);

        $cartForm = $this->createForm(PanierType::class);
        $cartForm->handleRequest($request);
        
        if($produit->isActive() == true) {
            if ($cartForm->isSubmitted() && $cartForm->isValid()) {
                $quantity = (int) $cartForm->get('quantity')->getData();
            
                if ($quantity < 1) {
                    $this->addFlash('danger', 'La quantité doit être supérieure à 0 !');
                } else {
                    $cart = $session->get('panier', []);
            
                    if (isset($cart[$produit->getId()])) {
                        $cart[$produit->getId()]['quantite'] += $quantity;
                    } else {
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
            
                    $session->set('panier', $cart);
                    $this->addFlash('success', 'Produit ajouté au panier !');
            
                    // Création de la redirection avec le cookie
                    $categorie = $produit->getCategorie()->getNomCategorie();
                    $route = $categorie === 'Sucré' ? 'sweety_produit' : 'salty_produit';
            
                    $response = new RedirectResponse($this->generateUrl($route));
                    $response->headers->setCookie(
                        Cookie::create(
                            'panier_backup',
                            json_encode($cart),
                            time() + 3600 * 24 * 7, // 7 jours
                            '/', // Dispo sur tout le site
                            null, // Domaine par defaut - aucun
                            true, // Mode HTTPS - activé
                            true, // httpOnly
                            false, // Laisse symfony encoder le cookie
                            'strict' // SameSite - évite le vol de cookie
                        )
                    );
            
                    return $response;
                }
            }
        }

        return $this->render('produit/show.html.twig', [
            'produit' => $produit,
            'cartForm' => $cartForm->createView(),
            'commentForm' => $commentForm->createView(),
            'commentaires' => $commentaires,
            'hasOrdered' => $hasOrdered,
            'allergenes' => $produit->getAllergenes(),
            'totalCommentaires' => $totalCommentaires,
        ]);
    }
    #[Route('/produit/{slug}/modifier-commentaire/{commentId}', name: 'produit_modifier_commentaire', methods: ['GET', 'POST'])]
    public function modifierCommentaire(
        Produit $produit,
        Commentaire $commentaire,
        int $commentId,
        CommentaireRepository $commentaireRepository,  
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser(); 
    
        $commentaire = $commentaireRepository->find($commentId);
    
        if (!$commentaire) {
            throw $this->createNotFoundException('Commentaire introuvable.');
        }
    
        if ($user !== $commentaire->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier ce commentaire.');
        }
    

        $form = $this->createForm(UpdateCommentaireType::class, $commentaire);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Mise à jour de la date et du produit
            if (!$commentaire->getDateCommentaire()) {
                $commentaire->setDateCommentaire(new DateTime());
            }
            $commentaire->setProduit($produit);
    
            $em->flush();
    
            $this->addFlash('success', 'Votre commentaire a été modifié.');
    
            // Retourne une réponse JSON
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => true, 'message' => 'Commentaire modifié avec succès.']);
            }
    
            return $this->redirectToRoute('produit_detail', ['slug' => $produit->getSlug()]);
        }

        return $this->render('produit/_edit_comment_form.html.twig', [
            'form' => $form->createView(),
            'produit' => $produit,
            'commentaire' => $commentaire,
        ]);
    }

    #[Route('/commentaire/{id}/supprimer', name: 'commentaire_supprimer', methods: ['POST'])]
    public function supprimer(
        Commentaire $commentaire, 
        EntityManagerInterface $em, 
        Security $security, 
        Request $request, 
        CsrfTokenManagerInterface $csrfTokenManager,
        ): Response
    {

        $produit = $commentaire->getProduit();
        $token = new CsrfToken('delete' . $commentaire->getId(), $request->request->get('_token'));
        
        if (!$csrfTokenManager->isTokenValid($token)) {
            return $this->redirectToRoute('produit_detail', ['slug' => $produit->getSlug()]);
        }

        if ($security->isGranted('ROLE_ADMIN') || $this->getUser() === $commentaire->getUser()) {
            $em->remove($commentaire);
            $em->flush();

            $this->addFlash('success', 'Commentaire supprimé avec succès.');
        } else {
            $this->addFlash('error', 'Vous n\'avez pas le droit de supprimer ce commentaire.');
        }

        return $this->redirectToRoute('produit_detail', ['slug' => $produit->getSlug()]);
    }

    // Fonction récupérant tous les commentaires d'un produit 
    #[Route('/produit/{slug}/commentaires', name: 'produit_all_comments', methods: ['GET'])]
    public function displayCommentaires(
        Produit $produit,
        Request $request,
        EntityManagerInterface $em,
        CommentaireRepository $commentaireRepository
    ): Response {
        $user = $this->getUser(); 
        
        $commentaire = new Commentaire();
        $commentForm = $this->createForm(CommentaireType::class, $commentaire);
    
        if ($user) {
            $commentForm->handleRequest($request);
    
            if ($commentForm->isSubmitted() && $commentForm->isValid()) {
                $commentaire->setUser($user);
                $commentaire->setProduit($produit);
                $commentaire->setDateCommentaire(new DateTime());
    
                $em->persist($commentaire);
                $em->flush();
    
                $this->addFlash('success', 'Votre commentaire a été ajouté avec succès.');
    
                return $this->redirectToRoute('produit_all_comments', ['slug' => $produit->getSlug()]);
            }
        }
    
        $commentaires = $commentaireRepository->findCommentairesByProduit($produit);
    
        return $this->render('produit/all_comments.html.twig', [
            'produit' => $produit,
            'commentaires' => $commentaires,
            'commentForm' => $commentForm->createView(),
        ]);
    }
}
