<?php

namespace App\Cart\Domain;

use Ramsey\Uuid\Uuid;

final class CartId
{
    private string $id;

    private function __construct(string $id)
    {
        $this->id = $id;
    }

    public static function generate(): self
    {
        return new self(Uuid::uuid4()->toString());
    }

    public static function fromString(string $id): self
    {
        // basic validation using Ramsey (throws if invalid)
        if (!Uuid::isValid($id)) {
            throw new \InvalidArgumentException('Invalid CartId format');
        }

        return new self($id);
    }

    public function __toString(): string
    {
        return $this->id;
    }

    public function equals(self $other): bool
    {
        return $this->id === $other->id;
    }

    public function value(): string
    {
        return $this->id;
    }
}
