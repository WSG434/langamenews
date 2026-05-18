<?php

namespace App\Tests\Unit\Service\Confirmation;

use App\Entity\ConfirmationCode;
use App\Entity\User;
use App\Service\Confirmation\ConfirmationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class ConfirmationServiceTest extends TestCase
{
    private ConfirmationService $service;

    protected function setUp(): void
    {
        $em = $this->createStub(EntityManagerInterface::class);

        $this->service = new ConfirmationService($em);
    }

    public function testGenerateProduces6DigitCode(): void
    {
        $user = $this->makeUser();
        $code = $this->service->generate($user);

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code->getCode());
    }

    public function testGenerateProducesDifferentCodes(): void
    {
        $user = $this->makeUser();
        $codes = [];
        for ($i = 0; $i < 10; $i++) {
            $codes[] = $this->service->generate($user)->getCode();
        }

        $this->assertGreaterThan(1, count(array_unique($codes)));
    }

    public function testValidateSuccessOnCorrectCode(): void
    {
        $user = $this->makeUser();
        $code = $this->makeCode($user, '123456');

        $this->service->validate($code, '123456');

        $this->assertSame(ConfirmationCode::STATUS_CONFIRMED, $code->getStatus());
        $this->assertTrue($user->isVerified());
    }

    public function testValidateThrowsOnExpiredCode(): void
    {
        $user = $this->makeUser();
        $code = $this->makeCode($user, '123456', expired: true);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('expired');

        $this->service->validate($code, '123456');
    }

    public function testValidateThrowsOnTooManyAttempts(): void
    {
        $user = $this->makeUser();
        $code = $this->makeCode($user, '123456');

        for ($i = 0; $i < 5; $i++) {
            $code->incrementAttempts();
        }

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('too_many_attempts');

        $this->service->validate($code, '123456');
    }

    public function testValidateThrowsOnInvalidCode(): void
    {
        $user = $this->makeUser();
        $code = $this->makeCode($user, '123456');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('invalid');

        $this->service->validate($code, '000000');
    }

    public function testValidateAlreadyConfirmedIsIdempotent(): void
    {
        $user = $this->makeUser();
        $code = $this->makeCode($user, '123456');
        $code->markConfirmed();

        $this->service->validate($code, 'wrong');

        $this->assertSame(ConfirmationCode::STATUS_CONFIRMED, $code->getStatus());
    }

    private function makeUser(): User
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('hashed');
        return $user;
    }

    private function makeCode(User $user, string $code, bool $expired = false): ConfirmationCode
    {
        $ttl = $expired ? -1 : 15;
        return new ConfirmationCode($user, $code, $ttl);
    }
}
