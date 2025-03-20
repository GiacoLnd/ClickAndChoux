<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Form\LivraisonType;
use App\Repository\ProduitRepository;
use App\Service\DeliveryTimeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class CommandeController extends AbstractController
{
    #[Route('/panier/valider', name: 'commande_valider', methods: ['POST'])]
    public function validerPanier(SessionInterface $session): Response
    {
        $user = $this->getUser();
    
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
    
        // Récupère le panier
        $cart = $session->get('panier', []);
    
        if (empty($cart)) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('panier_afficher');
        }
    
        // Redirection vers confirmation de commande
        return $this->redirectToRoute('commande_confirmer');
    }
    


    #[Route('/commande/confirmer', name: 'commande_confirmer')]
    public function confirmerCommande(
        Request $request,
        SessionInterface $session,
        DeliveryTimeService $deliveryTimeService,
        ProduitRepository $produitRepository,
    ): Response {
        $user = $this->getUser();
    
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
    
        $cart = $session->get('panier', []);

        
        if (empty($cart)) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('panier_afficher');
        }
        
        foreach ($cart as $key => $item) {
            $produit = $produitRepository->find($item['id']);
            if ($produit) {
                $cart[$key]['slug'] = $produit->getSlug();
                $cart[$key]['image'] = $produit->getImage();
            }
        }

        $session->set('panier', $cart);
    
        // Calcul du montant total (avec frais de livraison)
        $total = 0.0;
        $deliveryFees = 300; // En centimes
        foreach ($cart as $item) {
            $total += $item['prixTTC'] * $item['quantite'] * 100; // Conversion en centimes
        }
        $total += $deliveryFees;
    
        // Calcul de la date de livraison
        $orderDate = (new \DateTime())->format('Y-m-d'); 
        $isHoliday = $deliveryTimeService->isHoliday($orderDate);
        $deliveryDate = $deliveryTimeService->calculateDeliveryDate('12:00', $isHoliday);
    
        // Préparation de la commande en session
        $commandeSession = $session->get('commande', [
            'user' => $user->getId(),
            'total' => $total / 100, // Stocké en euros
            'delivery_date' => $deliveryDate->format('Y-m-d'),
            'cart' => $cart,
            'adresseLivraison' => '',
            'codePostalLivraison' => '',
            'villeLivraison' => '',
        ]);
    
        $form = $this->createForm(LivraisonType::class, $commandeSession);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Mise à jour des infos de livraison dans session
            $data = $form->getData();
            $commandeSession['adresseLivraison'] = $data['adresseLivraison'];
            $commandeSession['codePostalLivraison'] = $data['codePostalLivraison'];
            $commandeSession['villeLivraison'] = $data['villeLivraison'];
            $session->set('commande', $commandeSession);
    
            $this->addFlash('success', 'Adresse de livraison enregistrée. Vous allez être redirigé vers le paiement.');
    
            // Redirection vers le paiement Stripe
            return $this->redirectToRoute('payment_stripe');
        }
    
        return $this->render('commande/confirmation.html.twig', [
            'commande' => $commandeSession,
            'form' => $form->createView(),
            'delivery_date' => $deliveryDate->format('d/m/Y'),
            'delivery_fees' => $deliveryFees / 100,
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
