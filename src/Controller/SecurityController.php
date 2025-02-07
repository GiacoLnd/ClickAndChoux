<?php

namespace App\Controller;

use App\Entity\Panier;
use App\Entity\Produit;
use App\Entity\Commande;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        $this->redirectToRoute('app_home');
        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    
    #[Route('/login/success', name: 'login_success')]
    public function loginSuccess(SessionInterface $session, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
    
        // ✅ Vérifie s'il y a une commande en attente pour l'utilisateur
        $commandeRepository = $entityManager->getRepository(Commande::class);
        $panierRepository = $entityManager->getRepository(Panier::class);
    
        $ancienneCommande = $commandeRepository->findOneBy([
            'user' => $user,
            'statut' => 'panier'
        ]);
    
        // ✅ Supprime l'ancienne commande et ses produits pour repartir de zéro
        if ($ancienneCommande) {
            foreach ($panierRepository->findBy(['commande' => $ancienneCommande]) as $panier) {
                $entityManager->remove($panier);
            }
            $entityManager->remove($ancienneCommande);
            $entityManager->flush();
        }
    
        // ✅ Si un panier en session existe, le convertir en commande
        $panierSession = $session->get('panier', []);
    
        if (!empty($panierSession)) {
            $nouvelleCommande = new Commande();
            $nouvelleCommande->setStatut('panier');
            $nouvelleCommande->setDateCommande(new \DateTime());
            $nouvelleCommande->setUser($user);
    
            $entityManager->persist($nouvelleCommande);
    
            foreach ($panierSession as $produitId => $quantite) {
                $produit = $entityManager->getRepository(Produit::class)->find($produitId);
    
                if ($produit) {
                    $panier = new Panier();
                    $panier->setProduit($produit);
                    $panier->setQuantity($quantite);
                    $panier->setCommande($nouvelleCommande);
    
                    $entityManager->persist($panier);
                }
            }
    
            $entityManager->flush();
    
            // ✅ Supprime le panier en session après transfert
            $session->remove('panier');
        }
    
        // ✅ Récupérer la redirection stockée (par défaut : aller au panier)
        $redirectUrl = $session->get('redirect_after_login', $this->generateUrl('panier_afficher'));
        $session->remove('redirect_after_login');
    
        return $this->redirect($redirectUrl);
    }
    
    
    
    
    
    

}
