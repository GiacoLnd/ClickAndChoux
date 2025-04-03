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

        $historique = $commande->getHistorique();

        $totalHT = 0;

        // Calcul total ht
        foreach ($historique['produits'] as $produit) {
            // prix ht pour chaque produit x quantité de chaque produit
            $totalHTProduit = $produit['prixHt'] * $produit['quantite'];
            // Ajout total ht de chaque produit au total ht global
            $totalHT += $totalHTProduit;
        }
        
        // Calcul montant tva
        $TVA = $totalHT * 0.055;
        
        // Chemin absolu du logo (nécessaire pour DomPDF)
        $logoPath = $_SERVER['DOCUMENT_ROOT'] . '/img/logo.webp';  // correspond à $logoPath = '/var/www/html/public/img/logo.webp'; donne le chemin absolu
        $logoBase64 = base64_encode(file_get_contents($logoPath)); // file_get_contents : lit le contenu du fichier, base64_encode : encode le contenu en base64 pour une lecture HTML
        
        $html = $this->twig->render('invoice/invoice.html.twig', [
            'commande' => $commande,
            'historique' => $commande->getHistorique(),
            'totalHT' => $totalHT,
            'montantTVA' => $TVA,
            'logoBase64' => $logoBase64
        ]);
    
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
    
        return $dompdf->output();
    }
    
}
