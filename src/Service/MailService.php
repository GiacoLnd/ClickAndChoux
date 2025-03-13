<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\File;
use Symfony\Component\Mime\Part\Multipart;
use Symfony\Component\Mime\Part\TextPart;
use Twig\Environment;

class MailService
{
    private MailerInterface $mailer;
    private Environment $twig;

    public function __construct(MailerInterface $mailer, Environment $twig)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
    }

    public function sendInvoiceEmail(string $to, string $pdfContent, string $fileName, $commande)
    {
        $logoPath = $_SERVER['DOCUMENT_ROOT'] . '/img/logo.png';

        // Convertion du logo en Base64
        $logoBase64 = base64_encode(file_get_contents($logoPath));
        $logoMimeType = mime_content_type($logoPath);
    
        // Génération de l'e-mail HTML
        $htmlContent = $this->twig->render('invoice/invoice_email.html.twig', [
            'user' => $commande->getUser(),
            'commande' => $commande,
            'url_facture' => 'http://127.0.0.1:8000/facture/'.$commande->getSlug(),
            'logoBase64' => $logoBase64,
            'logoMimeType' => $logoMimeType
        ]);
    
        // Création de l'e-mail
        $email = (new Email())
            ->from('contact@clickandchoux.com')
            ->to($to)
            ->subject('Votre Facture - Click&Choux')
            ->html($htmlContent) 
            ->attach($pdfContent, $fileName, 'application/pdf');
    
        // Envoi de l'e-mail
        $this->mailer->send($email);
    }
}