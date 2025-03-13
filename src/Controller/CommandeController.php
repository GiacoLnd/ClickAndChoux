<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Form\LivraisonType;
use App\Service\DeliveryTimeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class CommandeController extends AbstractController
{
    #[Route('/panier/valider', name: 'commande_valider', methods: ['POST'])]
    public function validerPanier(EntityManagerInterface $entityManager, Security $security): Response
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

        // Protection de la propriété de la commande des modifications de l'URL
        if (!$security->isGranted('ROLE_ADMIN') && $commande->getUser() !== $user) {
            throw $this->createAccessDeniedException("Accès non-autorisé");
        }

        if (!$commande || $commande->getPaniers()->isEmpty()) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('panier_afficher');
        }
        
        return $this->redirectToRoute('commande_confirmer');
    }


    #[Route('/commande/confirmer', name: 'commande_confirmer')]
    public function confirmerCommande(
        Request $request,
        EntityManagerInterface $entityManager,
        Security $security,
        DeliveryTimeService $deliveryTimeService
    ): Response {
        $user = $this->getUser();
    
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
    
        // Récupération commande
        $commande = $entityManager->getRepository(Commande::class)->findOneBy([
            'user' => $user,
            'statut' => 'panier'
        ]);
    
        if (!$commande || $commande->getPaniers()->isEmpty()) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('panier_afficher');
        }
    
        if (!$security->isGranted('ROLE_ADMIN') && $commande->getUser() !== $user) {
            throw $this->createAccessDeniedException("Accès non autorisé");
        }
    
        // Génération référence unique
        if (!$commande->getReference()) {
            do {
                $reference = 'CMD-' . strtoupper(bin2hex(random_bytes(4)));
            } while ($entityManager->getRepository(Commande::class)->findOneBy(['reference' => $reference]));
    
            $commande->setReference($reference);
        }
    
        // Calcul du montant total avec frais de livraison (mis à jour immédiatement)
        $total = 0.0;
        $deliveryFees = 300; // Stripe fonctionne en centimes - garde la même logique
        foreach ($commande->getPaniers() as $panier) {
            $total += $panier->getProduit()->getTTC() * $panier->getQuantity() * 100; // Conversion en centimes
        }
        $total += $deliveryFees;
        $commande->setMontantTotal($total / 100); // Stocké en euros
    
        // Calcul date livraison
        $orderDate = (new \DateTime())->format('Y-m-d'); 
        $isHoliday = $deliveryTimeService->isHoliday($orderDate);
        $deliveryDate = $deliveryTimeService->calculateDeliveryDate($commande->getDateCommande()?->format('H:i') ?? '12:00', $isHoliday);
        $commande->setDateLivraison($deliveryDate);
    
        // Flush immédiat pour date livraison et Montant total
        $entityManager->flush();
    
        // Si l'utilisateur laisse finalement sa commande en attente et qu'il revient plus tard, le fait de revenir sur cette page mets automatiquement à jour la date de livraison

        // Formulaire adresse de livraison
        $form = $this->createForm(LivraisonType::class, $commande);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Mise à jour statut commande
            $commande->setStatut("en attente de paiement");
            $entityManager->flush();
    
            $this->addFlash('success', 'Adresse de livraison enregistrée. Vous allez être redirigé vers le paiement.');
    
            // Redirection paiement Stripe
            return $this->redirectToRoute('payment_stripe', ['reference' => $commande->getReference()]);
        }

        return $this->render('commande/confirmation.html.twig', [
            'commande' => $commande,
            'form' => $form->createView(),
            'delivery_date' => $deliveryDate->format('d/m/Y'),
        ]);
    }
    
    
    #[Route('/commande/confirmation/{slug}', name: 'commande_confirmation')]
    public function confirmationCommande(Commande $commande, Security $security): Response
    {
        $user = $this->getUser();

        if (!$security->isGranted('ROLE_ADMIN') && $commande->getUser() !== $user) {
            throw $this->createAccessDeniedException("Accès non-autorisé");
        }
        return $this->render('commande/index.html.twig', [
            'commande' => $commande
        ]);
    }


    #[Route('/commande/{slug}', name: 'commande_detail', methods: ['GET'])]
    public function detailCommande(Commande $commande, Security $security): Response
    {
        $user = $this->getUser();

        if (!$security->isGranted('ROLE_ADMIN') && $commande->getUser() !== $user) {
            throw $this->createAccessDeniedException("Vous n'avez pas accès à cette commande.");
        }

        return $this->render('commande/detail.html.twig', [
            'commande' => $commande,
            'user' => $user,
        ]);
    }
}
