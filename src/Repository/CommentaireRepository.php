<?php

namespace App\Repository;

use App\Entity\Produit;
use App\Entity\Commentaire;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Commentaire>
 */
class CommentaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commentaire::class);
    }

    //    /**
    //     * @return Commentaire[] Returns an array of Commentaire objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Commentaire
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function findCommentairesByProduit(Produit $produit, int $page = 1, int $limit = 5)
    {
        return $this->createQueryBuilder('c')
            ->where('c.produit = :produit')
            ->setParameter('produit', $produit)
            ->orderBy('c.DateCommentaire', 'DESC')
            ->setFirstResult(($page - 1) * $limit)  // Calcul de l'offset - de 1 à 5 comm. pour la page 1 - de 6 à 10 comm. pour la page 2
            // Exemple offset page 1 : (1 - 1) * 5 = 0 - du commentaire 1 à 5 pour la page 1
            ->setMaxResults($limit)  // Limite de 5 commentaires
            ->getQuery()
            ->getResult();
    }

    public function countCommentairesByProduit(Produit $produit)
    {
    return $this->createQueryBuilder('c')
        ->select('COUNT(c.id)')
        ->where('c.produit = :produit')
        ->setParameter('produit', $produit)
        ->getQuery()
        ->getSingleScalarResult(); // -> récupère un seul résultat 
    }
    
}
