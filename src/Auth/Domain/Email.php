<?php

namespace App\Auth\Domain;

final class Email
{
    private string $value;

    private function __construct(string $value)
    {
        $normalized = trim(mb_strtolower($value));
        if (!filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(sprintf('Email "%s" no es válido.', $value));
        }
        $this->value = $normalized;
    }

    public static function fromString(string $email): self
    {
        return new self($email);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value();
    }
}
