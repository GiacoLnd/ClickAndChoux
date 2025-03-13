<?php 

namespace App\Controller;

use Stripe\Stripe;
use App\Entity\Produit;
use App\Entity\Commande;
use Stripe\Checkout\Session;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PaymentController extends AbstractController{

    private EntityManagerInterface $em;
    private UrlGeneratorInterface $generator;

    public function __construct(EntityManagerInterface $em, UrlGeneratorInterface $generator)
    {
        $this->em = $em;
        $this->generator = $generator;
    }
    #[Route('/commande/create-session-stripe/{reference}', name: 'payment_stripe')]
    public function StripeCheckout($reference):RedirectResponse
    {
        $productStripe = [];

        $commande = $this->em->getRepository(Commande::class)->findOneBy(['reference' => $reference]);
        
        if (!$commande){
            $this->redirectToRoute('panier_afficher');
        }

        foreach($commande->getPaniers() as $panier){
            $productData = $this->em->getRepository(Produit::class)->findOneBy(['nomProduit' => $panier->getProduit()->getNomProduit()]);
            $productStripe[] = [
                'price_data' => [
                        'currency' => 'eur',
                        'unit_amount' => $productData->getTTC()*100, // x100 car Stripe fonctionne en centimes
                        'product_data' => [
                            'name' => $productData->getNomProduit(),
                        ]
                ],
                'quantity' => $panier->getQuantity(),
            ];
        }

        $productStripe[] = [
            'price_data' => [
                'currency' => 'eur',
                'unit_amount' => 300, // Stripe fonctionne en centimes - 3€ de frais de livraison
                'product_data' => [
                    'name' => 'Frais de livraison',
                ]
            ],
            'quantity' => 1,
        ];

        Stripe::setApiKey('sk_test_51R1pUOHxeRAfTDA8PykJyMYP8Fc2K9JQPzAsB3f2yRW2L2LwO9t7A86sHI9FMavsUm2pkikgWm5ub7eLqtXuKx4f00XTuD6RRg');

        $checkout_session = Session::create([
            'customer_email' => $this->getUser()->getEmail(),
            'payment_method_types' => ['card'],
            'line_items' => [[
                $productStripe
            ]],
            'mode' => 'payment',
            'success_url' => $this->generator->generate('payment_success', [
                'reference' => $commande->getReference()
            ], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->generator->generate('payment_error', [
                'reference' => $commande->getReference()
            ], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        $commande->setDatePaiement(new \DateTime());
        $commande->setStripeSessionId($checkout_session->id);
        $this->em->flush();

        return new RedirectResponse($checkout_session->url);

    }


    #[Route('/commande/success/{reference}', name: 'payment_success')]
    public function StripeSuccess(string $reference, EntityManagerInterface $em, Security $security): Response
    {
        $commande = $em->getRepository(Commande::class)->findOneBy(['reference' => $reference]);
    
        if (!$commande) {
            throw $this->createNotFoundException("Commande non trouvée !");
        }
    
        $user = $this->getUser();
    
        // Restriction d’accès
        if (!$security->isGranted('ROLE_ADMIN') && $commande->getUser() !== $user) {
            throw $this->createAccessDeniedException("Accès non autorisé");
        }
    
        // Mise à jour Statuts
        $commande->setStatutPaiement("payé");
        $commande->setStatut("en cours");
    
        $em->persist($commande);
        $em->flush();
    
        $this->addFlash('success', 'Votre paiement a été validé !');
    
        return $this->redirectToRoute('commande_confirmation', ['slug' => $commande->getSlug()]);
    }

    #[Route('/commande/error/{reference}', name: 'payment_error')]
    public function StripeError(string $reference, EntityManagerInterface $em, Security $security): Response
    {
        $commande = $em->getRepository(Commande::class)->findOneBy(['reference' => $reference]);

        if (!$commande) {
            throw $this->createNotFoundException("Commande non trouvée !");
        }

        $user = $this->getUser();

        // Restriction d'accès
        if (!$security->isGranted('ROLE_ADMIN') && $commande->getUser() !== $user) {
            throw $this->createAccessDeniedException("Accès non autorisé");
        }

        // Mise à jour Statut paiement uniquement
        $commande->setStatutPaiement("échec paiement");

        $em->persist($commande);
        $em->flush();

        $this->addFlash('error', 'Le paiement a échoué. Veuillez réessayer.');

        return $this->redirectToRoute('commande_confirmer', ['slug' => $commande->getSlug()]);
    }
}




