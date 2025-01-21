<?php

namespace App\Repository;

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
    //    }


    public function findPanierItem(Commande $commande, Produit $produit): ?Panier
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.commande = :commande')
            ->andWhere('p.produit = :produit')
            ->setParameter('commande', $commande)
            ->setParameter('produit', $produit)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function addOrUpdateProduct(Commande $commande, Produit $produit, int $quantity): void
    {
        $panierItem = $this->findPanierItem($commande, $produit);

        if ($panierItem) {
            // Si le produit existe déjà, mettre à jour la quantité
            $panierItem->setQuantity($panierItem->getQuantity() + $quantity);
        } else {
            // Sinon, créer un nouvel élément de panier
            $panierItem = new Panier();
            $panierItem->setCommande($commande);
            $panierItem->setProduit($produit);
            $panierItem->setQuantity($quantity);
            $this->_em->persist($panierItem);
        }

        $this->_em->flush();
    }

     public function removeProduct(Commande $commande, Produit $produit): void
    {
        $panierItem = $this->findPanierItem($commande, $produit);

        if ($panierItem) {
            $this->_em->remove($panierItem);
            $this->_em->flush();
        }
    }

    public function clearPanier(Commande $commande): void
    {
        $this->createQueryBuilder('p')
            ->delete()
            ->andWhere('p.commande = :commande')
            ->setParameter('commande', $commande)
            ->getQuery()
            ->execute();
    }
}
