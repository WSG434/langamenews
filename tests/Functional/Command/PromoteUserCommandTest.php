<?php

namespace App\Tests\Functional\Command;

use App\Command\PromoteUserCommand;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PromoteUserCommandTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    private function createUser(string $email): User
    {
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($hasher->hashPassword($user, 'password123'));
        $user->setIsVerified(true);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    private function runCommand(string $email): CommandTester
    {
        $command = static::getContainer()->get(PromoteUserCommand::class);
        $tester = new CommandTester($command);
        $tester->execute(['email' => $email]);
        return $tester;
    }

    public function testPromoteExistingUserGrantsAdminRole(): void
    {
        $this->createUser('promote@test.com');

        $tester = $this->runCommand('promote@test.com');

        $this->assertSame(0, $tester->getStatusCode());

        $this->em->clear();
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => 'promote@test.com']);
        $this->assertContains('ROLE_ADMIN', $user->getRoles());
    }

    public function testPromoteNonExistentUserFails(): void
    {
        $tester = $this->runCommand('nobody@test.com');
        $this->assertSame(1, $tester->getStatusCode());
    }
}
