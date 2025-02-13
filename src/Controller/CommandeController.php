<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Form\CommandeType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class CommandeController extends AbstractController
{
    #[Route('/panier/valider', name: 'commande_valider', methods: ['POST'])]
    public function validerPanier(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Vérifie si l'utilisateur a une commande en cours
        $commande = $entityManager->getRepository(Commande::class)->findOneBy([
            'user' => $user,
            'statut' => 'panier'
        ]);

        if (!$commande || $commande->getPaniers()->isEmpty()) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('panier_afficher');
        }
        
        return $this->redirectToRoute('commande_confirmer');
    }


    #[Route('/commande/confirmer', name: 'commande_confirmer')]
    public function confirmerCommande(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
    
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
    
        // Récupère la commande "panier"
        $commande = $entityManager->getRepository(Commande::class)->findOneBy([
            'user' => $user,
            'statut' => 'panier'
        ]);
    
        if (!$commande || $commande->getPaniers()->isEmpty()) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('panier_afficher');
        }
    
        // Génération d'une référence unique si elle n'existe pas
        if (!$commande->getReference()) {
            do {
                $reference = 'CMD-' . strtoupper(bin2hex(random_bytes(4)));
            } while ($entityManager->getRepository(Commande::class)->findOneBy(['reference' => $reference]));
    
            $commande->setReference($reference);
        }
    
        // Calcul du montant total
        $total = 0.0;
        foreach ($commande->getPaniers() as $panier) {
            $total += $panier->getProduit()->getTTC() * $panier->getQuantity();
        }
        $commande->setMontantTotal($total);
    
        // Création du formulaire d'adresse de livraison
        $form = $this->createForm(CommandeType::class, $commande);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $commande->setStatut('confirmée');
            $commande->setDateCommande(new \DateTime());
    
            $entityManager->flush();
            $this->addFlash('success', 'Commande confirmée avec succès !');
    
            return $this->redirectToRoute('commande_confirmation', ['id' => $commande->getId()]);
        }
    
        return $this->render('commande/confirmation.html.twig', [
            'commande' => $commande,
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/commande/confirmation/{id}', name: 'commande_confirmation')]
    public function confirmationCommande(Commande $commande): Response
    {
        return $this->render('commande/index.html.twig', [
            'commande' => $commande
        ]);
    }

}
