<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Form\EditProfileType;
use App\Form\ChangePasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;


final class UserController extends AbstractController
{
    #[Route('/user', name: 'app_user')]
    public function index(): Response
    {
        return $this->render('user/index.html.twig', [
            'controller_name' => 'UserController',
        ]);
    }
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

    #[Route('/updateProfile', name: 'app_update_profile')]
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

    #[Route('/updatePassword', name: 'app_update_password')]
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

        // Récupère toutes les commandes confirmées de l'utilisateur
        $commandes = $entityManager->getRepository(Commande::class)->findBy(
            ['user' => $user, 'statut' => 'confirmée'],
            ['dateCommande' => 'DESC'] // Trier par date, de la plus récente à la plus ancienne
        );

        return $this->render('user/commandes.html.twig', [
            'commandes' => $commandes
        ]);
    }

    
}
