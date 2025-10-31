<?php

namespace App\Tests\Cart\Unit;

use PHPUnit\Framework\TestCase;
use App\Cart\Domain\Money;

final class MoneyTest extends TestCase
{
    public function test_from_cents_and_to_cents(): void
    {
        $m = Money::fromCents(150);
        $this->assertEquals(150, $m->toCents());
        $this->assertEquals(1.5, $m->toFloat());
    }

    public function test_from_float_and_to_float(): void
    {
        $m = Money::fromFloat(9.99);
        $this->assertEquals(999, $m->toCents());
        $this->assertEquals(9.99, $m->toFloat());
    }

    public function test_add_and_equals(): void
    {
        $a = Money::fromCents(100);
        $b = Money::fromCents(250);
        $sum = $a->add($b);

        $this->assertEquals(350, $sum->toCents());
        $this->assertTrue($sum->equals(Money::fromCents(350)));
    }

    public function test_multiply(): void
    {
        $m = Money::fromFloat(2.5);
        $this->assertEquals(250, $m->toCents());
        $this->assertEquals(5.0, $m->multiply(2)->toFloat());
    }

    public function test_negative_not_allowed(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Money::fromCents(-10);
    }
}

