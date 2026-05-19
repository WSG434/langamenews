<?php

namespace App\Repository;

use App\Entity\SiteSettings;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SiteSettings>
 */
class SiteSettingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private readonly EntityManagerInterface $em)
    {
        parent::__construct($registry, SiteSettings::class);
    }

    public function getCurrent(): SiteSettings
    {
        $settings = $this->findOneBy([]);
        if ($settings === null) {
            $settings = new SiteSettings();
            $this->em->persist($settings);
            $this->em->flush();
        }
        return $settings;
    }
}
