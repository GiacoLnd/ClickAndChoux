<?php

namespace App\Controller;

use App\Entity\Favoris;
use App\Entity\Produit;
use App\Repository\FavorisRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Response;

#[Route('/favoris', name: 'favoris_')]
class FavorisController extends AbstractController
{
    #[Route('/liste', name: 'liste', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function liste(FavorisRepository $favorisRepository): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non connecté'], 401); // Retourne une réponse JSON avec un code HTTP 401 (Unauthorized)
        }

        $favoris = $favorisRepository->findBy(['user' => $user]); // Récupère tous les objets Favoris liés à l’utilisateur connecté
        $favorisIds = array_map(fn($favori) => $favori->getProduit()->getId(), $favoris); // Transforme la liste des favoris en une liste d’IDs des produits favoris
        // fn : méthode courte pour créer une fonction anonyme (fonctionn sans nom utilisée uniquement une seule fois) 

        return new JsonResponse(['favoris' => $favorisIds]);
    }

    #[Route('/favoris/page', name: 'page', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function pageFavoris(FavorisRepository $favorisRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
    
        $favoris = $favorisRepository->findBy(['user' => $user]);
    
        return $this->render('favoris/index.html.twig', [
            'favoris' => $favoris,
        ]);
    }
    


    #[Route('/ajouter/{id}', name: 'ajouter_favoris', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function ajouter(Produit $produit, EntityManagerInterface $em, FavorisRepository $favorisRepository): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non connecté'], 401);
        }

        if (!$produit) {
            return new JsonResponse(['message' => 'Produit non trouvé'], 404);
        }

        if ($favorisRepository->findOneBy(['user' => $user, 'produit' => $produit])) {
            return new JsonResponse(['message' => 'Produit déjà en favoris'], 400);
        }

        $favoris = new Favoris();
        $favoris->setUser($user);
        $favoris->setProduit($produit);

        $em->persist($favoris);
        $em->flush();

        return new JsonResponse(['message' => 'Produit ajouté aux favoris']);
    }

    #[Route('/supprimer/{id}', name: 'supprimer_favoris', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function supprimer(Produit $produit, EntityManagerInterface $em, FavorisRepository $favorisRepository): JsonResponse
    {
        $user = $this->getUser();
        $favoris = $favorisRepository->findOneBy(['user' => $user, 'produit' => $produit]);

        if (!$favoris) {
            return new JsonResponse(['message' => 'Produit non trouvé'], 404);
        }

        $em->remove($favoris);
        $em->flush();

        return new JsonResponse(['message' => 'Produit retiré des favoris']);
    }
}

