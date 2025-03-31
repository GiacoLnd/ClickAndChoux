<?php
// src/Controller/NewsletterController.php
namespace App\Controller;

use App\Form\NewsletterType;
use App\Service\NewsletterService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class NewsletterController extends AbstractController
{
    #[Route('/newsletter-subscribe', name: 'newsletter_subscribe', methods: ['POST'])]
    public function subscribe(Request $request, NewsletterService $NewsletterService): Response
    {
        $form = $this->createForm(NewsletterType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $email = $data['email'];

            // Appel à votre service pour ajouter l'abonné à MailerLite
            $success = $NewsletterService->subscribeToNewsletter($email);

            if ($success) {
                $this->addFlash('success', 'Merci pour votre inscription à notre newsletter !');
            } else {
                $this->addFlash('error', 'Une erreur est survenue lors de l\'inscription');
            }

            // Redirige vers la page d'où vient la requête
            return $this->redirect($request->headers->get('referer'));
        }

        // Si le formulaire n'est pas valide, on redirige à la page d'accueil
        return $this->redirectToRoute('app_home');
    }

    public function newsletterForm(): Response
    {
        $form = $this->createForm(NewsletterType::class, null, [
            'action' => $this->generateUrl('newsletter_subscribe'),
            'method' => 'POST'
        ]);

        return $this->render('newsletter/_newsletterForm.html.twig', [
            'newsletter_form' => $form->createView(),
        ]);
    }
}
