<?php

namespace App\Tests\Product\Unit;

use App\Product\Domain\ValueObject\Money;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function testEqualsSame(): void
    {
        $a = new Money(10.00, 'EUR');
        $b = new Money(10.0, 'EUR');

        $this->assertTrue($a->equals($b));
    }

    public function testDifferentCurrency(): void
    {
        $a = new Money(10.00, 'EUR');
        $b = new Money(10.00, 'USD');

        $this->assertFalse($a->equals($b));
    }
}

