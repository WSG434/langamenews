<?php

namespace App\Event;

use App\Entity\User;

final class UserRegisteredEvent
{
    public function __construct(public readonly User $user) {}
}
