<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Commande;
use App\Entity\Panier;
use App\Entity\Produit;

class LoginListener implements EventSubscriberInterface
{
    private RouterInterface $router;
    private RequestStack $requestStack;
    private EntityManagerInterface $entityManager;

    public function __construct(RouterInterface $router, RequestStack $requestStack, EntityManagerInterface $entityManager)
    {
        $this->router = $router;
        $this->requestStack = $requestStack;
        $this->entityManager = $entityManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event)
    {
        $user = $event->getUser();
        $session = $this->requestStack->getSession();

        // Vérifie si l'utilisateur a déjà une commande "panier"
        $commande = $this->entityManager->getRepository(Commande::class)->findOneBy([
            'user' => $user,
            'statut' => 'panier'
        ]);

        // Vérifie si un panier en session existe
        $panierSession = $session->get('panier', []);

        if (!empty($panierSession)) {
            // Supprime l'ancienne commande si elle existe
            if ($commande) {
                foreach ($commande->getPaniers() as $panier) {
                    $this->entityManager->remove($panier);
                }
                $this->entityManager->remove($commande);
                $this->entityManager->flush();
            }

            // Créé une nouvelle commande "panier"
            $nouvelleCommande = new Commande();
            $nouvelleCommande->setStatut('panier');
            $nouvelleCommande->setDateCommande(new \DateTime());
            $nouvelleCommande->setUser($user);

            // Génère une référence unique pour la commande
            do {
                $reference = 'CMD-' . strtoupper(bin2hex(random_bytes(4))); // bin2hex() convertit les 4 bytes randoms * 2 en chaîne hexadécimale - random_bytes(4) génère 4 octets aléatoires
            } while ($this->entityManager->getRepository(Commande::class)->findOneBy(['reference' => $reference]));

            $nouvelleCommande->setReference($reference);

            $this->entityManager->persist($nouvelleCommande);

            // Calcule le montant total avant enregistrement
            $total = 0.0;

            foreach ($panierSession as $produitId => $quantity) {
                $produit = $this->entityManager->getRepository(Produit::class)->find($produitId);
                if ($produit) {
                    $panier = new Panier();
                    $panier->setProduit($produit);
                    $panier->setQuantity($quantity);
                    $panier->setCommande($nouvelleCommande);
                    $this->entityManager->persist($panier);

                    // Ajoute au montant total
                    $total += $produit->getTTC() * $quantity;
                }
            }

            $nouvelleCommande->setMontantTotal($total);

            $this->entityManager->flush();

            // Supprime le panier en session après transfert
            $session->remove('panier');
        }

        // Redirection après connexion vers la validation de commande
        $response = new RedirectResponse($this->router->generate('commande_valider'));
        $event->setResponse($response);
    }
}
