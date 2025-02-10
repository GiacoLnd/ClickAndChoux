<?php

namespace App\Controller;

use App\Entity\Commande;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class CommandeController extends AbstractController
{
    #[Route('/commande/valider', name: 'commande_valider')]
    public function validerCommande(EntityManagerInterface $entityManager): Response
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
            $this->addFlash('error', 'Votre commande est vide.');
            return $this->redirectToRoute('panier_afficher');
        }

        return $this->render('commande/validation.html.twig', [
            'commande' => $commande
        ]);
    }

    #[Route('/commande/confirmer', name: 'commande_confirmer')]
    public function confirmerCommande(EntityManagerInterface $entityManager): Response
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
            $this->addFlash('error', 'Aucune commande à valider.');
            return $this->redirectToRoute('panier_afficher');
        }
    
        // Vérifie si la commande a déjà une référence (évite la génération en double)
        if (!$commande->getReference()) {
            do {
                $reference = 'CMD-' . strtoupper(bin2hex(random_bytes(4))); // bin2hex() convertit les octets en chaîne hexadécimale - random_bytes(4) génère 4 octets aléatoires
                // 8 caractères hexadécimaux car bin2hex() convertit 4 bytes * 2 = 8 bytes en hexadécimal
            } while ($entityManager->getRepository(Commande::class)->findOneBy(['reference' => $reference]));
    
            $commande->setReference($reference);
        }
    
        // Calcule le montant total
        $total = 0.0;
        foreach ($commande->getPaniers() as $panier) {
            $total += $panier->getTotalTTC();
        }
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
