<?php 

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Environment;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\KernelEvents;
use App\Entity\Commande;
use App\Entity\Panier;

class NavbarListener
{
    private Environment $twig;
    private RequestStack $requestStack;
    private TokenStorageInterface $tokenStorage;
    private EntityManagerInterface $entityManager;

    public function __construct(Environment $twig, RequestStack $requestStack, TokenStorageInterface $tokenStorage, EntityManagerInterface $entityManager)
    {
        $this->twig = $twig;
        $this->requestStack = $requestStack;
        $this->tokenStorage = $tokenStorage;
        $this->entityManager = $entityManager;
    }

    #[AsEventListener(event: KernelEvents::CONTROLLER, priority: 100)]
    public function onKernelController(ControllerEvent $event): void
    {
        // Initialisation de la quantité
        $quantity = 0;
    
        // Récupération de la session
        $session = $this->requestStack->getSession();
    
        if ($session->has('panier')) {
            $panier = $session->get('panier', []);
    
            // Calcul de la quantité totale des produits en panier
            foreach ($panier as $productId => $item) {
                if (is_array($item) && isset($item['quantite'])) {
                    $quantity += (int) $item['quantite']; // Préparation d'un int pour la valeur de $quantity
                }
            }
        }
        
        // Partage de la quantité avec tous les templates Twig
        $this->twig->addGlobal('quantity', $quantity);
    }
}

