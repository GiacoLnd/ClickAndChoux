<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Commande;
use App\Form\EditProfileType;
use Doctrine\ORM\EntityManager;
use App\Form\ChangePasswordType;
use App\Repository\UserRepository;
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    
            // Ajout d'un message flash
            $this->addFlash('success', 'Mot de passe modifié avec succès.');
    
            // Rediriger vers le profil sans passer par la page de connexion
            return $this->redirectToRoute('admin_profile');
        }
    
        // Affichage du formulaire
        return $this->render('admin/user_edit_password.html.twig', [
            'user' => $user,
            'formPassword' => $formPassword->createView(),
        ]);
    }

}