<?php 
namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RequestStack;

class PanierRestoreSubscriber implements EventSubscriberInterface
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack; // injection de dépendance - gérer la pile de requête HTTP
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();


        $session = $request->getSession();

        // Si pas de session et cookie de restauration existe, reconstruction du panier
        if (!$session->has('panier') && $request->cookies->has('panier_backup')) {
            $cookieData = json_decode($request->cookies->get('panier_backup'), true);

            if (is_array($cookieData)) {
                $panierReconstruit = [];

                foreach ($cookieData as $item) {
                    if (
                        isset($item['id'], $item['quantite']) &&
                        is_numeric($item['id']) &&
                        is_numeric($item['quantite'])
                    ) {
                        $panierReconstruit[$item['id']] = $item;
                    }
                }

                $session->set('panier', $panierReconstruit);
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 20],
        ];
    }
}
