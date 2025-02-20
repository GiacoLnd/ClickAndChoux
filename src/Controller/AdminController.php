<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Produit;
use App\Entity\Commande;
use App\Form\ProduitType;
use App\Form\EditProfileType;
use Doctrine\ORM\EntityManager;
use App\Form\ChangePasswordType;
use App\Repository\UserRepository;
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
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[Route('/admin', name: 'admin_')]
#[IsGranted('ROLE_ADMIN')]
final class AdminController extends AbstractController
{
    #[Route('/dashboard', name: 'profile')]
    public function dashboardAdmin(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        
        return $this->render('admin/index.html.twig', [
            'user' => $user,
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
    #[Route('/admin/produit/ajouter', name: 'add_product')]
    public function ajouterProduit(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $produit = new Produit();
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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
        ]);
    }

}