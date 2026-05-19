<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserNotificationSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserNotificationSetting>
 */
class UserNotificationSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserNotificationSetting::class);
    }

    public function findForUser(User $user): ?UserNotificationSetting
    {
        return $this->findOneBy(['user' => $user]);
    }

    public function findForUserId(int $userId): ?UserNotificationSetting
    {
        return $this->createQueryBuilder('s')
            ->where('IDENTITY(s.user) = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
