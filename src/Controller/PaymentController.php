<?php 

namespace App\Controller;

use Stripe\Stripe;
use App\Entity\Panier;
use App\Entity\Produit;
use App\Entity\Commande;
use Stripe\Checkout\Session;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Cocur\Slugify\Slugify;

class PaymentController extends AbstractController{

    private EntityManagerInterface $em;
    private UrlGeneratorInterface $generator;

    public function __construct(EntityManagerInterface $em, UrlGeneratorInterface $generator)
    {
        $this->em = $em;
        $this->generator = $generator;
    }
    #[Route('/create-session-stripe', name: 'payment_stripe')]
    public function StripeCheckout(SessionInterface $session, UrlGeneratorInterface $generator): RedirectResponse
    {
        // Récupère la commande en session
        $commandeSession = $session->get('commande');
        dump($commandeSession);
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
    
        Stripe::setApiKey('sk_test_51R1pUOHxeRAfTDA8PykJyMYP8Fc2K9JQPzAsB3f2yRW2L2LwO9t7A86sHI9FMavsUm2pkikgWm5ub7eLqtXuKx4f00XTuD6RRg');
    
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
    public function StripeSuccess(SessionInterface $session, EntityManagerInterface $em): Response
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
        $commande->setStatut("en cours");
        $commande->setMontantTotal($commandeSession['total']);
        $commande->setDateLivraison(new \DateTime($commandeSession['delivery_date']));
        $commande->setAdresseLivraison($commandeSession['adresseLivraison']);
        $commande->setCodePostalLivraison($commandeSession['codePostalLivraison']);
        $commande->setVilleLivraison($commandeSession['villeLivraison']);
        $commande->setDatePaiement(new \DateTime());
   
        if (!empty($commandeSession['stripe_session_id'])) {
            $commande->setStripeSessionId($commandeSession['stripe_session_id']);
        }
    
        // Génération de la référence unique 
        do {
            $reference = 'CMD-' . strtoupper(bin2hex(random_bytes(4)));
        } while ($em->getRepository(Commande::class)->findOneBy(['reference' => $reference]));
    
        $commande->setReference($reference);

        // Insertion du slug de la commandedans la base de données
        $slugify = new Slugify();
        $slug = $slugify->slugify($reference);
        $commande->setSlug($slug);
    
        // Création de l'historique de la commande 
        $historiqueCommande = [
            'reference' => $reference,
            'dateCommande' => (new \DateTime())->format('Y-m-d H:i:s'),
            'datePaiement' => (new \DateTime())->format('Y-m-d H:i:s'),
            'montantTotal' => $commandeSession['total'],
            'statutPaiement' => "payé",
            'adresseLivraison' => $commandeSession['adresseLivraison'],
            'codePostalLivraison' => $commandeSession['codePostalLivraison'],
            'villeLivraison' => $commandeSession['villeLivraison'],
            'dateLivraison' => $commandeSession['delivery_date'],
            'produits' => [],
        ];

        // Ajout des produits commandés
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
    
        // Suppression de la commande de la session
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




