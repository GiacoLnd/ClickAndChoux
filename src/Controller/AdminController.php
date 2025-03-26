<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Contact;
use App\Entity\Produit;
use App\Entity\Commande;
use App\Entity\Allergene;
use App\Form\AddProduitType;
use App\Form\EditProfileType;
use App\Form\DeleteProduitType;
use App\Form\UpdateProduitType;
use Doctrine\ORM\EntityManager;
use App\Form\ChangePasswordType;
use App\Repository\UserRepository;
use App\Repository\ContactRepository;
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Stripe\Stripe;
use Stripe\Balance;

#[Route('/admin', name: 'admin_')]
#[IsGranted('ROLE_ADMIN')]
final class AdminController extends AbstractController
{
    public function __construct()
    {
        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']); 
    }

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

    #[Route('/utilisateurs', name: 'list_user')]
    public function listUser(UserRepository $userRepository): Response
    {
        
        $users = $userRepository->findOnlyUsers();    

        return $this->render('admin/utilisateurs.html.twig', [
            'users' => $users,
        ]);
    }   
    
    #[Route('/utilisateur/commandes', name: 'list_commandes')]
    public function listeCommandes(CommandeRepository $commandeRepository, ): Response
    {
        $statutExclu = 'panier';
        $commandes = $commandeRepository->findCommandesSansStatut($statutExclu);
            

        return $this->render('admin/list_commandes.html.twig', [
            'commandes' => $commandes,
        ]);
    }

    #[Route('/utilisateur/{id}/commandes', name: 'user_commandes')]
    public function listeCommandesUser(User $user, CommandeRepository $commandeRepository, ): Response
    {
        $commandes = $commandeRepository->findBy(['user' => $user]); 
            

        return $this->render('admin/user_commandes.html.twig', [
            'user' => $user,
            'commandes' => $commandes,
        ]);
    }

    #[Route('/utilisateurs/{id}/updateProfile', name: 'user_edit_profile')]
    public function adminUpdateProfile(User $user, Request $request, EntityManagerInterface $entityManager): Response
    {
        $formProfile = $this->createForm(EditProfileType::class, $user);
        $formProfile->handleRequest($request);
        
        if ($formProfile->isSubmitted() && $formProfile->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash('success', 'Informations personnelles mises à jour.');
            return $this->redirectToRoute('admin_list_user');
        }

        return $this->render('admin/user_edit_profile.html.twig', [
            'user' => $user,
            'formProfile' => $formProfile->createView(),
        ]);
    }
    #[Route('/utilisateurs/{id}/updatePassword', name: 'user_edit_password')]
    public function adminUpdatePassword(User $user, Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        // Création du formulaire de changement de mot de passe
        $formPassword = $this->createForm(ChangePasswordType::class);
        $formPassword->handleRequest($request);
    
        if ($formPassword->isSubmitted() && $formPassword->isValid()) {

            // Récupération du nouveau mot de passe
            $newPassword = $formPassword->get('plainPassword')->getData();
    
            // Hachage du mot de passe via bcrypt
            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);
    
            $entityManager->persist($user);
            $entityManager->flush();
    
            $this->addFlash('success', 'Mot de passe modifié avec succès.');
    
            return $this->redirectToRoute('admin_profile');
        }
    
        // Affichage du formulaire
        return $this->render('admin/user_edit_password.html.twig', [
            'user' => $user,
            'formPassword' => $formPassword->createView(),
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
            
            if($produit->getNomProduit()){
                $produit->generateSlug();
            }

            // Récupération de l'image transmise
            $imageFile = $form->get('image')->getData();
            // Gestion du nom du fichier
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename); // Slug : transformation d'un nom de fichier en une chaîne de caractères sécurisée compatible
            //Génération d'un nom de fichier unique (nom du fichier sluggé + id unique + extention)
            $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
        
            try {
                $imageFile->move(
                    $this->getParameter('images_directory'),
                    $newFilename
                );
            } catch (FileException $e) {
                $this->addFlash('danger', 'Erreur lors du téléchargement de l\'image.');
                return $this->redirectToRoute('admin_produit_ajouter');
            }
        
            $produit->setImage($newFilename);
        
            $entityManager->persist($produit);
            $entityManager->flush();
        
            $this->addFlash('success', 'Produit ajouté avec succès !');
            return $this->redirectToRoute('admin_profile');
        }

        return $this->render('admin/add_product.html.twig', [
            'form' => $form->createView(),
            'produit' => $produit,
        ]);
    }

    // Function to remove one or multiple products 
    #[Route('/admin/produit/supprimer', name: 'delete_product')]
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
    
            if ($produit->getCategorie() && $produit->getCategorie()->getNomCategorie() === 'Sucré') {
                return $this->redirectToRoute('sweety_produit');
            } elseif ($produit->getCategorie() && $produit->getCategorie()->getNomCategorie() === 'Salé') {
                return $this->redirectToRoute('salty_produit');
            }
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
        // Fais passer de non-lu en lu à l'ouverture des détails du contact
        if ($contact->isread() == false) {
            $contact->setIsRead(true);
            $em->flush();
        }

        return $this->render('admin/contact_details.html.twig', [
            'contact'=> $contact,
        ]);
    }

}