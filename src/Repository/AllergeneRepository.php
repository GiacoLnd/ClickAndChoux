<?php

namespace App\Repository;

use App\Entity\Allergene;
use App\Entity\Categorie;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Allergene>
 */
class AllergeneRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Allergene::class);
    }

    //    /**
    //     * @return Allergene[] Returns an array of Allergene objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Allergene
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    // Récupère les allergènes par catégorie de produit
    public function findAllergensByCategory(Categorie $categorie)
    {
    return $this->createQueryBuilder('a')
        ->join('a.produits', 'p') 
        ->where('p.categorie = :categorie')  
        ->setParameter('categorie', $categorie)
        ->groupBy('a.id') 
        ->getQuery()
        ->getResult();
    }

}
