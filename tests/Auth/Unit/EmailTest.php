<?php

namespace App\Tests\Auth\Unit;

use App\Auth\Domain\Email;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    public function testValidEmail(): void
    {
        $e = Email::fromString('User@Example.com ');
        $this->assertSame('user@example.com', $e->value());
        $this->assertSame('user@example.com', (string)$e);
    }

    public function testInvalidEmailThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Email::fromString('not-an-email');
    }
}

