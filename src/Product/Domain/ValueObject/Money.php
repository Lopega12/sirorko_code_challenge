<?php

namespace App\Product\Domain\ValueObject;

final class Money
{
    private float $amount;
    private string $currency;

    public function __construct(float $amount, string $currency = 'EUR')
    {
        $this->amount = round($amount, 2);
        $this->currency = strtoupper($currency);
    }

    public static function of(float $amount, string $currency = 'EUR'): self
    {
        return new self($amount, $currency);
    }

    public function amount(): float
    {
        return $this->amount;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function toArray(): array
    {
        return ['amount' => $this->amount, 'currency' => $this->currency];
    }

    public function equals(Money $other): bool
    {
        return $this->currency === $other->currency() && abs($this->amount - $other->amount()) < 0.01;
    }
}
