<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Form\EditProfileType;
use App\Form\DeleteAccountType;
use App\Form\ChangePasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;


final class UserController extends AbstractController
{
    #[Route('/profil', name: 'app_user_profil')]
    #[IsGranted('ROLE_USER')]
    public function profile(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $this->getUser();

        // Modification du mot de passe
        $formPassword = $this->createForm(ChangePasswordType::class);
        $formPassword->handleRequest($request);

        if ($formPassword->isSubmitted() && $formPassword->isValid()) {
            $newPassword = $formPassword->get('plainPassword')->getData();
            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Mot de passe modifié avec succès.');
            return $this->redirectToRoute('app_user_profile');
        }

        return $this->render('user/index.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/update/profil', name: 'app_update_profile')]
    public function updateProfile(Request $request, EntityManagerInterface $entityManager): Response {
        $user = $this->getUser();

         // Modification des données personnelles
         $formProfile = $this->createForm(EditProfileType::class, $user);
         $formProfile->handleRequest($request);
         
         if ($formProfile->isSubmitted() && $formProfile->isValid()) {
             $entityManager->persist($user);
             $entityManager->flush();
             $this->addFlash('success', 'Informations personnelles mises à jour.');
             return $this->redirectToRoute('app_user_profil');
         }

         return $this->render('user/updateProfile.html.twig', [
            'formProfile' => $formProfile->createView(),
        ]);
    }

    #[Route('/update/password', name: 'app_update_password')]
    public function updatePassword(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher,  TokenStorageInterface $tokenStorage): Response
    {
        $user = $this->getUser();
    
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
    
            $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
            $tokenStorage->setToken($token);
    
            // Ajout d'un message flash
            $this->addFlash('success', 'Mot de passe modifié avec succès.');
    
            // Rediriger vers le profil sans passer par la page de connexion
            return $this->redirectToRoute('app_user_profil');
        }
    
        // Affichage du formulaire
        return $this->render('user/updatePassword.html.twig', [
            'user' => $user,
            'formPassword' => $formPassword->createView(),
        ]);
    }

    #[Route('/profil/commandes', name: 'app_user_commandes')]
    public function listeCommandes(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $statutExclu = 'panier';
        // Récupère toutes les commandes confirmées de l'utilisateur
       $commandes = $entityManager->getRepository(Commande::class)->findCommandesUserSansStatut($user, $statutExclu);

        return $this->render('user/commandes.html.twig', [
            'commandes' => $commandes
        ]);
    }

    #[Route('/profil/supprimer', name: 'app_profile_delete', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function deleteAccount(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = $this->getUser();

        // Création du formulaire
        $formDelete = $this->createForm(DeleteAccountType::class);
        $formDelete->handleRequest($request);
        
        if($this->isGranted('ROLE_USER')){
            if ($formDelete->isSubmitted() && $formDelete->isValid()) {
                // Récupération du mot de passe soumis
                $password = $formDelete->get('password')->getData();
        
                // Vérification du mot de passe
                if (!$passwordHasher->isPasswordValid($user, $password)) {
                    $this->addFlash('error', 'Mot de passe incorrect.');
                    return $this->redirectToRoute('app_profile_delete');
                }
        
                // Dissocier les commandes de l'utilisateur
                foreach ($user->getCommandes() as $commande) {
                    $commande->setUser(null);
                }
                
                $entityManager->flush(); // Enregistre la dissociation avant suppression
        
                // Suppression de l'utilisateur
                    $entityManager->remove($user);
                    $entityManager->flush();
        
                // Déconnecter l'utilisateur
                $this->container->get('security.token_storage')->setToken(null);
                $request->getSession()->invalidate();
        
                $this->addFlash('success', 'Votre compte a été supprimé avec succès.');
                return $this->redirectToRoute('app_home');
            }
        }
    
        return $this->render('user/deleteAccount.html.twig', [
            'formDelete' => $formDelete->createView(),
        ]);
    }
    
    
    
}