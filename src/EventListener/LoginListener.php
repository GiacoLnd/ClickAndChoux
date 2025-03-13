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

            // Création d'une nouvelle commande "panier"
            $nouvelleCommande = new Commande();
            $nouvelleCommande->setStatut('panier');
            $nouvelleCommande->setDateCommande(new \DateTime());
            $nouvelleCommande->setUser($user);

            // Génère une référence unique pour la commande
            do {
                $reference = 'CMD-' . strtoupper(bin2hex(random_bytes(4)));
                } 
            while ($this->entityManager->getRepository(Commande::class)->findOneBy(['reference' => $reference]));

            $nouvelleCommande->setReference($reference);
            $nouvelleCommande->generateSlug();

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

            $historiqueProduit = [
                'id' => $produit->getId(),
                'nomProduit' => $produit->getNomProduit(),
                'prixHt' => $produit->getPrixHt(),
                'TVA' => $produit->getTVA(),
                'prixTTC' => round($produit->getPrixHt() * (1 + $produit->getTVA() / 100), 2),
                'description' => $produit->getDescription(),
                'allergene' => $produit->getAllergenes(),
                'image' => $produit->getImage(),
                'categorie' => $produit->getCategorie() ? $produit->getCategorie()->getNomCategorie() : "Non défini",
                'quantite' => $quantity
            ];

            // Ajout produit dans l'historique de la commande
            $historiqueCommande = $nouvelleCommande->getHistorique();
            $historiqueCommande['produits'][] = $historiqueProduit;
            $nouvelleCommande->setHistorique($historiqueCommande);

            $this->entityManager->flush();

            // Supprime le panier en session après transfert
            $session->remove('panier');

            // Redirection vers la validation de commande
            $response = new RedirectResponse($this->router->generate('commande_valider'));
        } else {
            // Si le panier est vide, redirection vers l'accueil
            $response = new RedirectResponse($this->router->generate('app_home'));
        }

        $event->setResponse($response);
    }
}
