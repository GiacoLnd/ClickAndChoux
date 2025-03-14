<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Commande;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Commande>
 */
class CommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commande::class);
    }

    //    /**
    //     * @return Commande[] Returns an array of Commande objects
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

    //    public function findOneBySomeField($value): ?Commande
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function findCommandeAvecPaniers(int $commandeId): ?Commande
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.paniers', 'p')
            ->addSelect('p')
            ->where('c.id = :id')
            ->setParameter('id', $commandeId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findCommandesUserSansStatut(User $user, string $statutExclu) 
    {
        return $this->createQueryBuilder('c')
                    -> where('c.user = :user')
                    -> andWhere('c.statut != :statut')
                    -> setParameter('user', $user)
                    -> setParameter('statut', $statutExclu)
                    ->orderBy('c.dateCommande', 'DESC')
                    ->getQuery()
                    ->getResult();
    }
    public function findCommandesSansStatut(string $statutExclu) 
    {
        return $this->createQueryBuilder('c')
                    -> andWhere('c.statut != :statut')
                    -> setParameter('statut', $statutExclu)
                    ->orderBy('c.dateCommande', 'DESC')
                    ->getQuery()
                    ->getResult();
    }
}

