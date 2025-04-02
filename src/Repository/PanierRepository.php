<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Panier;
use App\Entity\Produit;
use App\Entity\Commande;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Panier>
 */
class PanierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Panier::class);
    }

    //    /**
    //     * @return Panier[] Returns an array of Panier objects
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

    //    public function findOneBySomeField($value): ?Panier
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    
    public function findBestSellerProductIds(int $limit = 6): array
{
    return $this->createQueryBuilder('pa')
        ->select('IDENTITY(pa.produit) as produitId') // Retourne uniquement l'ID de l'objet panier.produit
        ->groupBy('pa.produit')
        ->orderBy('SUM(pa.quantity)', 'DESC')
        ->setMaxResults($limit)
        ->getQuery()
        ->getScalarResult(); // retourne array d'ID
}
}
