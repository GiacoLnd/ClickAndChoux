<?php

namespace App\Controller;

use App\Entity\Produit;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PanierController extends AbstractController
{
    #[Route('/panier', name: 'panier')]
    public function index(SessionInterface $session): Response
    {
        $panier = $session->get('panier', []);

        $total = 0;
        foreach ($panier as $item) {
            $total += $item['produit']->getTTC() * $item['quantite'];
        }

        return $this->render('panier/index.html.twig', [
            'panier' => $panier,
            'total' => $total,
        ]);
    }

    #[Route('/panier/add/{id}', name: 'panier_add')]
    public function add(SessionInterface $session, Produit $produit, Request $request, ValidatorInterface $validator): Response
    {
        $panier = $session->get('panier', []);
    
        // Récupération de la quantité depuis la requête
        $quantite = $request->query->get('quantite', 1);
    
        
        $constraint = new Regex([
            'pattern' => '/^[0-9]*$/', // Validation de la quantité uniquement par des chiffres - exclut les nombres négatifs, les lettres et les symboles
            'message' => 'La quantité doit être un nombre valide',
        ]);
        //quantité déjà gérée en JS


        $errors = $validator->validate($quantite, $constraint);
    
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $this->addFlash('error', $error->getMessage());
            }
    
            return $this->redirectToRoute('app_home');
        }
    
        if (isset($panier[$produit->getId()])) {
            $panier[$produit->getId()]['quantite'] += (int)$quantite;
        } else {
            $panier[$produit->getId()] = [
                'produit' => $produit,
                'quantite' => $quantite,
            ];
        }
    
        $session->set('panier', $panier);
        
    
        return $this->redirectToRoute('panier');
    }

    #[Route('/panier/remove/{id}', name: 'panier_remove')]
    public function remove(SessionInterface $session, Produit $produit): Response
    {
        $panier = $session->get('panier', []);

        if (isset($panier[$produit->getId()])) {
            unset($panier[$produit->getId()]);
        }

        $session->set('panier', $panier);

        return $this->redirectToRoute('panier');
    }

    #[Route('/panier/clear', name: 'panier_clear')]
    public function clear(SessionInterface $session): Response
    {
        $session->remove('panier');

        return $this->redirectToRoute('panier');
    }

}

