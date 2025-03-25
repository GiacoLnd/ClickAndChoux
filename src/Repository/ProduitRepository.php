<?php

namespace App\Repository;

use App\Entity\Produit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Produit>
 */
class ProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Produit::class);
    }

    //    /**
    //     * @return Produit[] Returns an array of Produit objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Produit
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    // Fonction permettant de rechercher des produits par nom en fonction de la catégorie
    public function findBySearchQuery($query, $categorie)
    {
        $qb = $this->createQueryBuilder('p');
        
        $qb->where('p.nomProduit LIKE :query')
           ->andWhere('p.categorie = :categorie')
           ->setParameter('query', '%'.$query.'%')
           ->setParameter('categorie', $categorie)
           ->orderBy('p.nomProduit', 'ASC');
        
        return $qb->getQuery()->getResult();
    }
    public function findByExcludedAllergens(array $allergenesIds, $categorie)
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->leftJoin('p.categorie', 'c')
            ->andWhere('c.id = :categorieId')
            ->setParameter('categorieId', $categorie->getId());
    
        if (!empty($allergenesIds)) {
            $queryBuilder
                ->leftJoin('p.allergenes', 'a')
                ->groupBy('p.id')
                ->having('SUM(CASE WHEN a.id IN (:allergenesIds) THEN 1 ELSE 0 END) = 0') // Si allergènes exclus renvoi 1 et exclu le produit ou renvoi 0
                ->setParameter('allergenesIds', array_map('intval', $allergenesIds)); // Sécurisation des integer pour les id des allergen
        }
    
        return $queryBuilder->getQuery()->getResult();
    }


}
