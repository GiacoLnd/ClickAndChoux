<?php

namespace App\Controller;

use Dom\Entity;
use Stripe\Stripe;
use Stripe\Balance;
use App\Entity\User;
use App\Entity\Contact;
use App\Entity\Produit;
use App\Entity\Commande;
use App\Entity\Allergene;
use App\Form\AddProduitType;
use App\Form\DeleteUserType;
use App\Form\EditProfileType;
use App\Form\DeleteProduitType;
use App\Form\UpdateProduitType;
use App\Repository\UserRepository;
use App\Repository\ContactRepository;
use App\Repository\ProduitRepository;
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[Route('/admin', name: 'admin_')]
#[IsGranted('ROLE_ADMIN')]
final class AdminController extends AbstractController
{
    public function __construct()
    {
        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']); 
    }

    //Fonction d'affichage du dashboard admin
    #[Route('/dashboard', name: 'profile')]
    public function dashboardAdmin(): Response
    {
        $user = $this->getUser();

        try {
            $balance = Balance::retrieve();

            $availableBalance = $balance->available[0]->amount / 100;
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la récupération du solde Stripe: ' . $e->getMessage());
            $availableBalance = 0;
        }
        
        return $this->render('admin/index.html.twig', [
            'user' => $user,
            'availableBalance' => $availableBalance,
        ]);
    }

    //Fonction de listing utilisateur
    #[Route('/utilisateurs', name: 'list_user')]
    public function listUser(UserRepository $userRepository): Response
    {
        
        $users = $userRepository->findOnlyUsers();   
        
         

        return $this->render('admin/utilisateurs.html.twig', [
            'users' => $users,
        ]);
    }   
    
    //Fonction listant les commandes 
    #[Route('/utilisateur/commandes', name: 'list_commandes')]
    public function listeCommandes(EntityManagerInterface $em ): Response
    {
        $nouvellesCommandes =   $em->getRepository(Commande::class)->findBy(['statut' => 'En préparation']); // Retourne les commandes 'En préparation'
        $commandesEnLivraison =   $em->getRepository(Commande::class)->findBy(['statut' => 'En livraison']); 
        $commandesTerminées =   $em->getRepository(Commande::class)->findBy(['statut' => 'Livrée']);

        return $this->render('admin/list_commandes.html.twig', [
            'nouvellesCommandes' => $nouvellesCommandes,
            'commandesEnLivraison' => $commandesEnLivraison,
            'commandesTerminées' => $commandesTerminées,
        ]);
    }

    //Fonction listant les commandes d'un utilisateur
    #[Route('/utilisateur/{id}/commandes', name: 'user_commandes')]
    public function listeCommandesUser(User $user, CommandeRepository $commandeRepository, ): Response
    {
        $commandes = $commandeRepository->findBy(['user' => $user]); 
            

        return $this->render('admin/user_commandes.html.twig', [
            'user' => $user,
            'commandes' => $commandes,
        ]);
    }

    //Fonction d'édition du profil d'un utilisateur
    #[Route('/utilisateurs/{id}/updateProfile', name: 'user_edit_profile')]
    public function adminUpdateProfile(User $user, Request $request, EntityManagerInterface $entityManager): Response
    {
        $formProfile = $this->createForm(EditProfileType::class, $user);
        $formProfile->handleRequest($request);
        
        if ($formProfile->isSubmitted() && $formProfile->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash('success', 'Informations personnelles mises à jour.');
            return $this->redirectToRoute('admin_produits');
        }

        return $this->render('admin/user_edit_profile.html.twig', [
            'user' => $user,
            'formProfile' => $formProfile->createView(),
        ]);
    }

    // Function to add a product
    #[Route('/admin/produit/ajouter', name: 'add_product')]
    public function ajouterProduit(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $produit = new Produit();
        $form = $this->createForm(AddProduitType::class, $produit);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
    
            // Slug
            if ($produit->getNomProduit()) {
                $produit->generateSlug();
            }
    
            // Image
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();
    
                try {
                    $imageFile->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Erreur lors du téléchargement de l\'image.');
                    return $this->redirectToRoute('add_product');
                }
    
                $produit->setImage($newFilename);
            }
    
            // Ajout d'allergènes 
            $newAllergenes = $form->get('newAllergenes')->getData();
    
            foreach ($newAllergenes as $allergene) {
                $nomAllergene = ucfirst($allergene->getNomAllergene());
                $allergene->setNomAllergene($nomAllergene);
    
                $existing = $entityManager->getRepository(Allergene::class)->findOneBy([
                    'nomAllergene' => $nomAllergene
                ]);
    
                if ($existing) {
                    $produit->addAllergene($existing);
                } else {
                    $entityManager->persist($allergene);
                    $produit->addAllergene($allergene);
                }
            }
    
            $entityManager->persist($produit);
            $entityManager->flush();
    
            $this->addFlash('success', 'Produit ajouté avec succès !');
            return $this->redirectToRoute('admin_produits');
        }
    
        return $this->render('admin/add_product.html.twig', [
            'form' => $form->createView(),
            'produit' => $produit,
        ]);
    }
    

    // Function to remove one product
    #[Route('/admin/produit/supprimer', name: 'delete_produit')]
    public function deleteProduit(Request $request, EntityManagerInterface $entityManager): Response
    {
        $produits = $entityManager->getRepository(Produit::class)->findAll();
        $form = $this->createForm(DeleteProduitType::class, null, [
            'produits' => $produits,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $deletedProducts = $form->get('produits')->getData();
            
            foreach($deletedProducts as $product){
                $entityManager->remove($product);
            }
            $entityManager->flush();
            $this->addFlash('success', count($deletedProducts) . ' produits supprimés avec succès.');
            
            return $this->redirectToRoute('admin_profile');
        }

        return $this->render('admin/delete_product.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    //Fonction d'édition d'un produit
    #[Route('/produit/{slug}/modifier', name: 'update_product')]
    public function edit(Produit $produit, Request $request, EntityManagerInterface $em)
    {
        $form = $this->createForm(UpdateProduitType::class, $produit);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $imageName = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move(
                    $this->getParameter('images_directory'), 
                    $imageName
                );
                $produit->setImage($imageName);
            }
        
            $produit->setNomProduit($form->get('nomProduit')->getData());
            $produit->setPrixHt($form->get('prixHt')->getData());
            $produit->setDescription($form->get('description')->getData());
            $produit->setTVA($form->get('TVA')->getData());
            $produit->setCategorie($form->get('categorie')->getData());
            $produit->setIsActive($form->get('isActive')->getData());
        
            $newAllergenes = $form->get('newAllergenes')->getData();


            foreach ($newAllergenes as $allergene) {
                $nomAllergene = ucfirst($allergene->getNomAllergene());

                $allergene->setNomAllergene($nomAllergene);

                $existingAllergene = $em->getRepository(Allergene::class)->findOneBy([
                    'nomAllergene' => $allergene->getNomAllergene()
                ]);

                if ($existingAllergene) {
                    $produit->addAllergene($existingAllergene);
                } else {
                    $em->persist($allergene);
                    $produit->addAllergene($allergene);
                }
            }

            $em->flush();
            $this->addFlash('success', 'Produit modifié avec succès !');
    
            return $this->redirectToRoute('admin_produits');
        }

        return $this->render('admin/edit_product.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // Function to display contact list
    #[Route('/contact/liste', name:'contact_list')]
    public function contactList(ContactRepository $contactRepository): Response
    {
        $contactForms = $contactRepository->findAll();

        return $this->render('admin/contact_list.html.twig', [
            'contactForms'=> $contactForms,
        ]);
    }

    // Function to display contact details
    #[Route('/contact/{id}', name: 'contact_details', methods: ['GET'])]
    public function contactDetails(Contact $contact, EntityManagerInterface $em): Response
    {
        // Fais passer de non-lu en lu à l'ouverture des détails du
        if ($contact->isread() == false) {
            $contact->setIsRead(true);
            $em->flush();
        }

        return $this->render('admin/contact_details.html.twig', [
            'contact'=> $contact,
        ]);
    }

    // Function to display all product
    #[Route('/produits', name: 'produits')]
    public function displayProduits(ProduitRepository $produitRepository): Response
    {
        $produits = $produitRepository->findAll();

        return $this->render('admin/gestion_produit.html.twig', [
            'produits' => $produits,
        ]);
    }

    //Fonction de suppression d'un produit depuis la gestion des produits
    #[Route('/admin/produit/delete/{slug}', name: 'delete_product')]
    public function deleteProduct(Produit $produit, EntityManagerInterface $em): Response
    {
        $em->remove($produit);
        $em->flush();

        $this->addFlash('success', 'Produit supprimé avec succès');
    
        return $this->redirectToRoute('admin_produits');
    }

    //Fonction de mise ou de retrait du stock
    #[Route('/admin/produit/stock/{slug}', name: 'update_stock')]
    public function updateStock(Produit $produit, EntityManagerInterface $em): Response
    {
        if($produit->isActive() == true) {
            $produit->setIsActive(false);
            $this->addFlash('success', 'Produit mis hors stock');
        } else if($produit->isActive() == false) {
            $produit->setIsActive(true);
            $this->addFlash('success', 'Produit mis en stock');
        }

        $em->persist($produit);
        $em->flush();

        return $this->redirectToRoute('admin_produits');
    }

    //Fonction de suppression d'un utilisateur par l'admin
    #[Route('/admin/supprimer-utilisateur/{id}', name: 'supprimer_utilisateur')]
    #[IsGranted('ROLE_ADMIN')]
    public function supprimerUtilisateur(
        User $user, 
        Request $request, 
        EntityManagerInterface $entityManager
    ): Response {
        // CSRF secure
        if (!$this->isCsrfTokenValid('supprimer_utilisateur'.$user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('list_user');
        }
        
        try {
            // Anonymisation de chaque commande de l'utilisateur
            foreach ($user->getCommandes() as $commande) {
                $commande->setUser(null);
                $entityManager->persist($commande);
            }
            
            // Supprime l'utilisateur
            $entityManager->remove($user);
            $entityManager->flush();
            
            $this->addFlash('success', 'Utilisateur supprimé avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la suppression : ' . $e->getMessage());
        }
        
        return $this->redirectToRoute('admin_list_user');
    }
}
