<?php

namespace App\Tests\Smoke;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BootSmokeTest extends KernelTestCase
{
    public function testKernelBoots(): void
    {
        self::bootKernel();

        self::assertNotNull(self::$kernel);
    }
}
