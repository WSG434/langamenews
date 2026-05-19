<?php

namespace App\Repository;

use App\Entity\Notification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    /** @return Notification[] */
    public function findAfter(int $lastId): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.id > :lastId')
            ->setParameter('lastId', $lastId)
            ->orderBy('n.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(Notification $notification): void
    {
        $this->getEntityManager()->persist($notification);
        $this->getEntityManager()->flush();
    }
}
