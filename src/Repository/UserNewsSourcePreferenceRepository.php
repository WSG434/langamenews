<?php

namespace App\Repository;

use App\Entity\NewsSource;
use App\Entity\User;
use App\Entity\UserNewsSourcePreference;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserNewsSourcePreference>
 */
class UserNewsSourcePreferenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserNewsSourcePreference::class);
    }

    /** @return string[] disabled source codes for the given user */
    public function findDisabledSourceCodes(User $user): array
    {
        $rows = $this->createQueryBuilder('p')
            ->select('s.code')
            ->join('p.newsSource', 's')
            ->where('p.user = :user')
            ->andWhere('p.enabled = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->getScalarResult();

        return array_column($rows, 'code');
    }

    public function findForUser(User $user): array
    {
        return $this->findBy(['user' => $user]);
    }

    public function findOneForUserAndSource(User $user, NewsSource $source): ?UserNewsSourcePreference
    {
        return $this->findOneBy(['user' => $user, 'newsSource' => $source]);
    }
}
