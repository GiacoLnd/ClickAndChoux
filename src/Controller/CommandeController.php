<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class CommandeController extends AbstractController
{

    #[Route('/commande/valider', name: 'commande_valider')]
    public function validerCommande(EntityManagerInterface $entityManager, UserInterface $user): Response
    {
        // Récupérer la commande en attente
        $commande = $entityManager->getRepository(Commande::class)->findOneBy([
            'utilisateur' => $user,
            'statut' => 'panier'
        ]);
    
        if (!$commande) {
            $this->addFlash('error', 'Votre commande est vide.');
            return $this->redirectToRoute('panier_afficher');
        }
    
        return $this->render('commande/validation.html.twig', [
            'commande' => $commande
        ]);
    }
    
    
    #[Route('/commande/confirmer', name: 'commande_confirmer')]
    public function confirmerCommande(EntityManagerInterface $entityManager, UserInterface $user): Response
    {
        // Récupérer la commande
        $commande = $entityManager->getRepository(Commande::class)->findOneBy([
            'utilisateur' => $user,
            'statut' => 'panier'
        ]);
    
        if (!$commande) {
            $this->addFlash('error', 'Aucune commande à valider.');
            return $this->redirectToRoute('panier_afficher');
        }
    
        // Calculer le total
        $total = 0.0;
        foreach ($commande->getPaniers() as $panier) {
            $total += $panier->getTotalTTC();
        }
    
        // Mettre à jour la commande
        $commande->setMontantTotal($total);
        $commande->setStatut('confirmée');
        $commande->setDateCommande(new \DateTime());
    
        $entityManager->flush();
    
        return $this->redirectToRoute('commande_confirmation', ['id' => $commande->getId()]);
    }
    
    #[Route('/commande/confirmation/{id}', name: 'commande_confirmation')]
    public function confirmationCommande(Commande $commande): Response
    {
        return $this->render('commande/index.html.twig', [
            'commande' => $commande
        ]);
    }


}
