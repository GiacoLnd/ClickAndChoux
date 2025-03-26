<?php 

namespace App\Controller;

use Stripe\Stripe;
use App\Entity\Panier;
use App\Entity\Produit;
use App\Entity\Commande;
use Cocur\Slugify\Slugify;
use Stripe\Checkout\Session;
use App\Service\InvoiceGenerator;
use Symfony\Component\Mime\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Loader\Configurator\mailer;

class PaymentController extends AbstractController{

    private EntityManagerInterface $em;
    private UrlGeneratorInterface $generator;

    public function __construct(EntityManagerInterface $em, UrlGeneratorInterface $generator)
    {
        $this->em = $em;
        $this->generator = $generator;
    }
    #[Route('/create-session-stripe', name: 'payment_stripe')]
    public function StripeCheckout(SessionInterface $session, UrlGeneratorInterface $generator, Request $request): RedirectResponse
    {
        // Récupère la commande en session
        $commandeSession = $session->get('commande');

        if (!$commandeSession) {
            $this->addFlash('error', 'Aucune commande trouvée.');
            return $this->redirectToRoute('panier_afficher');
        }
    
        $productStripe = [];
    
        // Ajout des produits à Stripe
        foreach ($commandeSession['cart'] as $item) {
            $productStripe[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => $item['prixTTC'] * 100, // Stripe fonctionne en centimes
                    'product_data' => [
                        'name' => $item['nom'],
                    ],
                ],
                'quantity' => $item['quantite'],
            ];
        }
    
        // Ajout des frais de livraison
        $productStripe[] = [
            'price_data' => [
                'currency' => 'eur',
                'unit_amount' => 300, // 3€ de frais de livraison
                'product_data' => [
                    'name' => 'Frais de livraison',
                ],
            ],
            'quantity' => 1,
        ];
        $stripeSecretKey = $_ENV['STRIPE_SECRET_KEY'];
        
        Stripe::setApiKey($stripeSecretKey);
    
        // Création de la session de paiement
        $checkout_session = Session::create([
            'customer_email' => $this->getUser()->getEmail(),
            'payment_method_types' => ['card'],
            'line_items' => $productStripe,
            'mode' => 'payment',
            'success_url' => $generator->generate('payment_success', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $generator->generate('payment_error', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);
    
        // Stockage de l'id de session stripe en session
        $commandeSession['stripe_session_id'] = $checkout_session->id;
        $session->set('commande', $commandeSession);
    
        return new RedirectResponse($checkout_session->url);
    }
    


    #[Route('/success', name: 'payment_success')]
    public function StripeSuccess(SessionInterface $session, EntityManagerInterface $em, MailerInterface $mailer, InvoiceGenerator $invoiceGenerator): Response
    {
        $commandeSession = $session->get('commande');
    
        if (!$commandeSession) {
            $this->addFlash('error', 'Aucune commande trouvée.');
            return $this->redirectToRoute('panier_afficher');
        }
    
        $user = $this->getUser();
    
        // Création de la commande en base de données
        $commande = new Commande();
        $commande->setUser($user);
        $commande->setDateCommande(new \DateTime());
        $commande->setStatutPaiement("payé");
        $commande->setStatut('En préparation');
        $commande->setMontantTotal($commandeSession['total']);
        $commande->setDateLivraison(new \DateTime($commandeSession['delivery_date']));
        $commande->setDatePaiement(new \DateTime());
    
        if (!empty($commandeSession['stripe_session_id'])) {
            $commande->setStripeSessionId($commandeSession['stripe_session_id']);
        }
    
        // Ajout des infos de livraison et de facturation
        $commande->setNomLivraison($commandeSession['nomLivraison'] ?? '');
        $commande->setPrenomLivraison($commandeSession['prenomLivraison'] ?? '');
        $commande->setAdresseLivraison($commandeSession['adresseLivraison'] ?? '');
        $commande->setCodePostalLivraison($commandeSession['codePostalLivraison'] ?? '');
        $commande->setVilleLivraison($commandeSession['villeLivraison'] ?? '');
    
        $commande->setNomFacturation($commandeSession['nomFacturation'] ?? '');
        $commande->setPrenomFacturation($commandeSession['prenomFacturation'] ?? '');
        $commande->setAdresseFacturation($commandeSession['adresseFacturation'] ?? '');
        $commande->setCodePostalFacturation($commandeSession['codePostalFacturation'] ?? '');
        $commande->setVilleFacturation($commandeSession['villeFacturation'] ?? '');
    
        // Génération de la référence unique 
        do {
            $reference = 'CMD-' . strtoupper(bin2hex(random_bytes(4)));
        } while ($em->getRepository(Commande::class)->findOneBy(['reference' => $reference]));
    
        $commande->setReference($reference);
    
        // Génération du slug pour la commande
        $slugify = new Slugify();
        $slug = $slugify->slugify($reference);
        $commande->setSlug($slug);
    
        // Création de l'historique de la commande 
        $historiqueCommande = [
            'reference' => $reference,
            'dateCommande' => (new \DateTime())->format('Y-m-d H:i:s'),
            'datePaiement' => (new \DateTime())->format('Y-m-d H:i:s'),
            'montantTotal' => round($commandeSession['total'], 2), // arrondi à deux décimales
            'user' => [
                'pseudo' => $commande->getUser()->getNickName(),
                'email'=> $commande->getUser()->getEmail(),
            ],
            'statutPaiement' => "payé",
            'adresseLivraison' => [
                'nom' => $commandeSession['nomLivraison'] ?? '',
                'prenom' => $commandeSession['prenomLivraison'] ?? '',
                'adresse' => $commandeSession['adresseLivraison'] ?? '',
                'codePostal' => $commandeSession['codePostalLivraison'] ?? '',
                'ville' => $commandeSession['villeLivraison'] ?? '',
            ],
            'adresseFacturation' => [
                'nom' => $commandeSession['nomFacturation'] ?? '',
                'prenom' => $commandeSession['prenomFacturation'] ?? '',
                'adresse' => $commandeSession['adresseFacturation'] ?? '',
                'codePostal' => $commandeSession['codePostalFacturation'] ?? '',
                'ville' => $commandeSession['villeFacturation'] ?? '',
            ],
            'dateLivraison' => $commandeSession['delivery_date'],
            'produits' => [],
        ];
    
        // Ajout des produits
        foreach ($commandeSession['cart'] as $item) {
            $produit = $em->getRepository(Produit::class)->find($item['id']);
            if ($produit) {
                $panier = new Panier();
                $panier->setProduit($produit);
                $panier->setQuantity($item['quantite']);
                $panier->setCommande($commande);
                $em->persist($panier);
    
                // Ajout des produits dans l'historique JSON de la table Commande
                $historiqueCommande['produits'][] = [
                    'id' => $produit->getId(),
                    'nom' => $produit->getNomProduit(),
                    'prixHt' => $produit->getPrixHt(),
                    'TVA' => $produit->getTVA(),
                    'prixTTC' => round($produit->getPrixHt() * (1 + $produit->getTVA() / 100), 2),
                    'quantite' => $item['quantite'],
                    'total' => round($produit->getPrixHt() * (1 + $produit->getTVA() / 100) * $item['quantite'], 2),
                ];
            }
        }
    
        $commande->setHistorique($historiqueCommande);
    
        // Enregistrement de la commande en base de données
        $em->persist($commande);
        $em->flush();

        // Création et envoi du mail de confirmation 
        // Utilisation du service de création de facture 
        $pdfContent = $invoiceGenerator->generateInvoicePdf($commande);
        // Insertion du slug de la commande en num de facture dans une variable
        $fileName = "facture-".$commande->getSlug().".pdf";

        $logoPath = $_SERVER['DOCUMENT_ROOT'] . '/img/logo.png';
        $logoBase64 = base64_encode(file_get_contents($logoPath));
        $logoMimeType = mime_content_type($logoPath);

        $email = (new Email())
        ->from('noreply@clickAndChoux.com')
        ->to($commande->getUser()->getEmail())
        ->subject('Votre commande a été validée !')
        ->html($this->renderView('commande/confirmation_mail.html.twig', [
            'nickName' => ucfirst($commande->getUser()->getNickName()),
            'montantTotal' => $commande->getMontantTotal(),
            'orderDate' => $commande->getDateCommande(),
            'deliveryDate' => $commande->getDateLivraison(),
            'reference' => $commande->getReference(),
            'produits' => $historiqueCommande['produits'],
            'logoBase64' => $logoBase64,
            'logoMimeType' => $logoMimeType
        ]));

        $email->attach($pdfContent, $fileName, 'application/pdf');

        // Envoie l'email
        $mailer->send($email);

        // Suppression des données en session
        $session->remove('commande');
        $session->remove('panier');
    
        $this->addFlash('success', 'Votre paiement a été validé !');
    
        return $this->redirectToRoute('commande_confirmation', ['slug' => $reference]);
    }
    
    
    

    #[Route('/commande/error', name: 'payment_error')]
    public function StripeError(SessionInterface $session): Response
    {
        // Vérifier si une commande existe en session
        $commandeSession = $session->get('commande');
    
        if (!$commandeSession) {
            $this->addFlash('error', 'Aucune commande trouvée.');
            return $this->redirectToRoute('panier_afficher');
        }
    
        $this->addFlash('error', 'Le paiement a échoué. Veuillez réessayer.');
    
        // Redirection vers récap de la commande pour nouvelle tentative de paiement
        return $this->redirectToRoute('commande_confirmer');
    }

}




