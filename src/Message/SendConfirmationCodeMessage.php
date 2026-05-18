<?php

namespace App\Message;

class SendConfirmationCodeMessage
{
    public function __construct(public readonly int $codeId) {}
}
