<?php

namespace App\Service\Confirmation;

use App\Entity\ConfirmationCode;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class ConfirmationService
{
    private const MAX_ATTEMPTS = 5;
    private const TTL_MINUTES = 15;

    public function __construct(private readonly EntityManagerInterface $em) {}

    public function generate(User $user): ConfirmationCode
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $confirmationCode = new ConfirmationCode($user, $code, self::TTL_MINUTES);

        $this->em->persist($confirmationCode);
        $this->em->flush();

        return $confirmationCode;
    }

    /**
     * @throws \DomainException with reason: 'expired' | 'too_many_attempts' | 'invalid'
     */
    public function validate(ConfirmationCode $code, string $input): void
    {
        if ($code->getStatus() === ConfirmationCode::STATUS_CONFIRMED) {
            return;
        }

        if ($code->isExpired()) {
            throw new \DomainException('expired');
        }

        if ($code->getAttempts() >= self::MAX_ATTEMPTS) {
            throw new \DomainException('too_many_attempts');
        }

        $code->incrementAttempts();

        if ($code->getCode() !== $input) {
            $this->em->flush();
            throw new \DomainException('invalid');
        }

        $code->markConfirmed();
        $code->getUser()->setIsVerified(true);
        $this->em->flush();
    }
}
