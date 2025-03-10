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
        // Cette requête va trouver les produits qui ne contiennent PAS les allergènes sélectionnés
        $queryBuilder = $this->createQueryBuilder('p')
            ->leftJoin('p.categorie', 'c')
            ->andWhere('c.id = :categorieId')
            ->setParameter('categorieId', $categorie->getId());
        
        if (!empty($allergenesIds)) {
            // Exclure les produits qui ont AU MOINS UN des allergènes sélectionnés
            $queryBuilder->andWhere('p.id NOT IN (
                SELECT DISTINCT pa.id 
                FROM App\Entity\Produit pa 
                JOIN pa.allergenes a 
                WHERE a.id IN (:allergenesIds)
            )')
            ->setParameter('allergenesIds', $allergenesIds);
        }
        
        return $queryBuilder->getQuery()->getResult();
    }
}
