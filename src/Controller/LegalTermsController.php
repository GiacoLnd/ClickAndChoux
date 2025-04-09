<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LegalTermsController extends AbstractController
{
    #[Route('/privacy', name: 'app_legal_privacy')]
    public function privacyTerms(): Response
    {
        return $this->render('legal_terms/privacy.html.twig', [
            'controller_name' => 'LegalTermsController',
        ]);
    }

    #[Route('/cgv', name: 'app_legal_sales')]
    public function salesConditions(): Response
    {
        return $this->render('legal_terms/sales_conditions.html.twig', [
            'controller_name' => 'LegalTermsController',
        ]);
    }
    #[Route('/cgu', name: 'app_legal_user_terms')]
    public function userTerms(): Response
    {
        return $this->render('legal_terms/user_terms.html.twig', [
            'controller_name' => 'LegalTermsController',
        ]);
    }
}
