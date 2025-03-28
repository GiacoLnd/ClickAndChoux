<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ErrorController extends AbstractController
{
    public function show(\Exception $exception): Response
    {
        $statusCode = 500; // Code d'erreur par défaut
        if ($exception instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
            $statusCode = $exception->getStatusCode();
        }
        
        // Affiche une page d'erreur personnalisée selon le code d'erreur
        return $this->render('error/error.html.twig', [
            'exception' => $exception,
            'status_code' => $statusCode,
        ]);
    }
}
