<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserTelegram;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserTelegramRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserTelegram::class);
    }

    public function findByToken(string $token): ?UserTelegram
    {
        return $this->findOneBy(['linkToken' => $token]);
    }

    public function findByChatId(int $chatId): ?UserTelegram
    {
        return $this->findOneBy(['chatId' => $chatId]);
    }

    public function findForUser(User $user): ?UserTelegram
    {
        return $this->findOneBy(['user' => $user]);
    }

    public function findLinkedChatId(int $userId): ?int
    {
        $result = $this->createQueryBuilder('t')
            ->select('t.chatId')
            ->where('t.user = :userId')
            ->andWhere('t.chatId IS NOT NULL')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();

        return $result ? $result['chatId'] : null;
    }
}
