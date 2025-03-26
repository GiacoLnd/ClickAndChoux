<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Form\LivraisonType;
use App\Form\FacturationType;
use App\Service\DeliveryTimeService;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Workflow\Registry;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
        ProduitRepository $produitRepository,
        DeliveryTimeService $deliveryTimeService
    ): Response {
        $user = $this->getUser();
    
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
    
        // Récupération du panier en session
        $cart = $session->get('panier', []);
    
        if (empty($cart)) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('panier_afficher');
        }
    
        // Calcul du montant total avec les frais de livrai
        $total = 0.0;
        $deliveryFees = 3.00; 
        foreach ($cart as &$item) { 
            $produit = $produitRepository->find($item['id']);
            if ($produit) {
                $item['slug'] = $produit->getSlug();
                $item['image'] = $produit->getImage();
                $total += $item['prixTTC'] * $item['quantite'];
            }
        }
        $total += $deliveryFees;
        $session->set('panier', $cart);
    
        // Calcul de la date de livraison
        $orderDate = (new \DateTime())->format('Y-m-d'); 
        $isHoliday = $deliveryTimeService->isHoliday($orderDate);
        $deliveryDate = $deliveryTimeService->calculateDeliveryDate('12:00', $isHoliday);
    
        // Récupération des données en session
        $commandeSession = $session->get('commande', [
            'user' => $user->getId(),
            'total' => $total,
            'delivery_date' => $deliveryDate->format('Y-m-d'),
            'cart' => $cart, 
            'nomLivraison' => '', 'prenomLivraison' => '', 'adresseLivraison' => '',
            'codePostalLivraison' => '', 'villeLivraison' => '',
            'nomFacturation' => '', 'prenomFacturation' => '', 'adresseFacturation' => '',
            'codePostalFacturation' => '', 'villeFacturation' => '',
        ]);
    
        $form = $this->createForm(LivraisonType::class, $commandeSession, [
            'csrf_protection' => true,
        ]);
    
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $commandeSession = array_merge($commandeSession, $form->getData());
            $session->set('commande', $commandeSession);
    
            $this->addFlash('success', 'Informations enregistrées.');
    
            return $this->redirectToRoute('payment_stripe');
        }
    
        return $this->render('commande/confirmation.html.twig', [
            'commande' => $commandeSession,
            'form' => $form->createView(),
            'delivery_date' => $deliveryDate->format('d/m/Y'),
            'delivery_fees' => $deliveryFees,
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

    // Fonction gérant le détail d'une commande
    #[Route('/commande/{slug}', name: 'commande_detail', methods: ['GET'])]
    public function detailCommande(Commande $commande, Security $security, WorkflowInterface $commandeWorkflow): Response
    {
        $user = $this->getUser();

        if (!$security->isGranted('ROLE_ADMIN') && $commande->getUser() !== $user) {
            throw $this->createAccessDeniedException("Vous n'avez pas accès à cette commande.");
        }

        // Vérifie les transitions disponibles
        $availableTransition = [];
        if ($commandeWorkflow->can($commande, 'start_delivery')) {
            $availableTransition[] = 'start_delivery';
        }
        if ($commandeWorkflow->can($commande, 'complete')) {
            $availableTransition[] = 'complete';
        }

        return $this->render('commande/detail.html.twig', [
            'commande' => $commande,
            'user' => $user,
            'availableTransition' => $availableTransition,
        ]);
    }

    // Fonction gérant le workflow du statut d'une commande En cours -> En livraison -> Terminée
    #[Route('/commande/{id}/update_status', name: 'update_status_commande',)]
   public function updateStatus(Commande $commande, WorkflowInterface $commandeWorkflow, EntityManagerInterface $em): RedirectResponse
   {
        // Condition de vérification de la transition
       if ($commandeWorkflow->can($commande, 'start_delivery')) {
        $commandeWorkflow->apply($commande, 'start_delivery');
        $this->addFlash('success', 'Commande en livraison !');
    } elseif ($commandeWorkflow->can($commande, 'complete')) {
        $commandeWorkflow->apply($commande, 'complete');
        $this->addFlash('success', 'Commande terminée !');
    } else {
        $this->addFlash('error', 'Transition impossible');
    }

    $em->flush();

    return $this->redirectToRoute('commande_detail', ['slug' => $commande->getSlug()]);
}
}
