<?php

namespace App\Controller;

use DateTime;
use Error;
use App\Entity\Contact;
use App\Form\ContactType;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class ContactController extends AbstractController
{

    // Function handling the contact form -> sent to admin pannel
    #[Route('/contact', name: 'app_contact')]
    public function contactForm(Request $request, EntityManager $entityManager): Response
    {
        $contact = new Contact();

        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            
            $email = $form->get('email');
            $phone = $form->get('telephone');
            $fax = $form->get('fax');

            if (empty($email) && empty($phone)) {
                $this->addFlash('error', "Pour pouvoir vous recontacter, nous avons besoin soit de votre email, soit de votre téléphone");
            }

            // Champs honeypot
            if (!empty($fax->getData())) {
                $this->addFlash('danger', 'Spam détecté. Vous avez rempli un champ caché');
                return $this->redirectToRoute('app_home');
            }

            if ($form->isValid()) {
                $contact->setDateContact(new DateTime());

                $entityManager->persist($contact);
                $entityManager->flush();
        
                $this->addFlash('success', "Votre message a été envoyé !");
        
                return $this->redirectToRoute('app_home');
            }
        }
    
        return $this->render('contact/index.html.twig', [
            'form' => $form,
        ]);
    }
}
