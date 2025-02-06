<?php

namespace App\Repository;

use App\Entity\Offer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\User;

/**
 * @extends ServiceEntityRepository<Offer>
 */
class OfferRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Offer::class);
    }

    //    /**
    //     * @return Offer[] Returns an array of Offer objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('o.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Offer
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function searchOffers(string $query): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.title LIKE :query OR o.description LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOffersForUser(User $user)
    {

        return $this->createQueryBuilder('o')
            ->leftJoin('o.skillWanted', 'sr') // Compétence requise pour l'offre
            ->leftJoin('o.user', 'u') // Ajout de la relation avec l'utilisateur qui a posté l'offre
            ->leftJoin('u.skills', 'us') // L'utilisateur connecté et ses compétences
            ->where('us.id = sr.id')  // Vérifie si l'utilisateur possède la compétence requise
            ->orWhere('o.isNegotiable = true') // Inclut les offres négociables
            ->getQuery()
            ->getResult();
    }

}
