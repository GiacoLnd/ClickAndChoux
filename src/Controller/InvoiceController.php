<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Service\MailService;
use App\Service\InvoiceGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class InvoiceController extends AbstractController
{
    // Génèration de la facture d'une commande pour téléchargement
    #[Route('/facture/{slug}', name: 'invoice_generate')]
    public function generateInvoice(Commande $commande, InvoiceGenerator $invoiceGenerator): Response
    {
        $pdf = $invoiceGenerator->generateInvoicePdf($commande);

        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="facture-'.$commande->getSlug().'.pdf"',
        ]);
    }

    // Génération de la facture pour envoi par mail
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/facture/envoyer/{slug}', name: 'invoice_send')]
    public function sendInvoice(Commande $commande, InvoiceGenerator $invoiceGenerator, MailService $mailService): Response
    {
        $user = $commande->getUser();
    
        if (!$user) {
            throw $this->createNotFoundException("Utilisateur non trouvé.");
        }
    
        // Génération du PDF
        $pdfContent = $invoiceGenerator->generateInvoicePdf($commande);
        $fileName = "facture-".$commande->getSlug().".pdf";
    
        // Envoi de l'e-mail
        $mailService->sendInvoiceEmail($user->getEmail(), $pdfContent, $fileName, $commande); // Fonction dans Service/MailService
    
        $this->addFlash('success', 'La facture a été envoyée par e-mail.');
    
        return $this->redirectToRoute('commande_detail', ['slug' => $commande->getSlug()]);
    }
    
}
