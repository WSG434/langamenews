<?php

namespace App\Service;

class CodeGenerator
{
    public static function numeric(int $digits = 6): string
    {
        return str_pad((string) random_int(0, (int) str_repeat('9', $digits)), $digits, '0', STR_PAD_LEFT);
    }
}
