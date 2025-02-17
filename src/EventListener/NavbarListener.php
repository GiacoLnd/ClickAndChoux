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
        // Retrieving the current token
        $token = $this->tokenStorage->getToken();
        
        // Extracting User from Token, else null
        $user = $token ? $token->getUser() : null;
    
        // If user connected
        if ($user) {
            // Fetching last (sorted by id DESC) command of the user with status "panier" 
            $commande = $this->entityManager->getRepository(Commande::class)
                ->findOneBy(['user' => $user, 'statut' => 'panier'], ['id' => 'DESC']);
            
            // fetching product from commande
            $panier = $commande ? $this->entityManager->getRepository(Panier::class)->findBy(['commande' => $commande]) : [];
            $result = 0;
            foreach($panier as $p){
                $result += $p->getQuantity();
            }
            $quantity = $result;
        } else {
            // If not connected, fetching product from session
            $session = $this->requestStack->getSession();
            $panier =$session->get('panier', []);
            $result = 0;
            foreach($panier as $p){
                $result += $p->getQuantity();
            }
            $quantity = $result;
        }
    
        // Sharing Panier with all TWIG templates
        $this->twig->addGlobal('quantity', $quantity);
    }
    
}
