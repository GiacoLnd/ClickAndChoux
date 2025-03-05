<?php

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class InvoiceGenerator
{
    private Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function generateInvoicePdf($commande): string
    {
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isHtml5ParserEnabled', true);
    
        $dompdf = new Dompdf($options);
    
        // Chemin absolu du logo (nécessaire pour DomPDF)
        $logoPath = $_SERVER['DOCUMENT_ROOT'] . '/img/logo.png';  // correspond à $logoPath = '/var/www/html/public/img/logo.png'; donne le chemin absolu
        $logoBase64 = base64_encode(file_get_contents($logoPath)); // file_get_contents : lit le contenu du fichier, base64_encode : encode le contenu en base64 pour une lecture HTML
        
        $html = $this->twig->render('invoice/invoice.html.twig', [
            'commande' => $commande,
            'historique' => $commande->getHistorique(),
            'logoBase64' => $logoBase64
        ]);
    
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
    
        return $dompdf->output();
    }
    
}
