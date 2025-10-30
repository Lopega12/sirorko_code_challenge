<?php

namespace App\Tests\Auth\Unit;

use App\Auth\Domain\PasswordHash;
use PHPUnit\Framework\TestCase;

final class PasswordHashTest extends TestCase
{
    public function testHashAndVerify(): void
    {
        $plain = 'secret123';
        $ph = PasswordHash::fromPlain($plain);

        $this->assertTrue($ph->verify($plain));
        $this->assertIsString($ph->value());
        $this->assertFalse($ph->verify('wrong'));
    }

    public function testFromHashAndEquals(): void
    {
        $plain = 'secret123';
        $ph1 = PasswordHash::fromPlain($plain);
        $ph2 = PasswordHash::fromHash($ph1->value());

        $this->assertTrue($ph2->verify($plain));
        $this->assertTrue($ph1->equals($ph2));
    }
}

