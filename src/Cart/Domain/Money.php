<?php

namespace App\Cart\Domain;

final class Money
{
    private int $cents;

    private function __construct(int $cents)
    {
        if ($cents < 0) {
            throw new \InvalidArgumentException('Money cannot be negative');
        }
        $this->cents = $cents;
    }

    public static function fromCents(int $cents): self
    {
        return new self($cents);
    }

    public static function fromFloat(float $amount): self
    {
        return new self((int) round($amount * 100));
    }

    public function add(Money $other): Money
    {
        return new self($this->cents + $other->cents);
    }

    public function multiply(int $times): Money
    {
        return new self($this->cents * $times);
    }

    public function toCents(): int
    {
        return $this->cents;
    }

    public function toFloat(): float
    {
        return $this->cents / 100;
    }

    public function equals(Money $other): bool
    {
        return $this->cents === $other->cents;
    }
}
