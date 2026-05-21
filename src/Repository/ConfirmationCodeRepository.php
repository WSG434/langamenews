<?php

namespace App\Repository;

use App\Entity\ConfirmationCode;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ConfirmationCode>
 */
class ConfirmationCodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConfirmationCode::class);
    }

    public function findPendingForUser(User $user): ?ConfirmationCode
    {
        return $this->createQueryBuilder('c')
            ->where('c.user = :user')
            ->andWhere('c.status = :status')
            ->andWhere('c.expiresAt > :now')
            ->setParameter('user', $user)
            ->setParameter('status', ConfirmationCode::STATUS_PENDING)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findActiveForUser(User $user): ?ConfirmationCode
    {
        return $this->createQueryBuilder('c')
            ->where('c.user = :user')
            ->andWhere('c.status IN (:statuses)')
            ->andWhere('c.expiresAt > :now')
            ->setParameter('user', $user)
            ->setParameter('statuses', [ConfirmationCode::STATUS_PENDING, ConfirmationCode::STATUS_SENT])
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
