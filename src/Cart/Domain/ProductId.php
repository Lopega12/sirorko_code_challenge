<?php

namespace App\Cart\Domain;

final class ProductId
{
    private string $id;

    private function __construct(string $id)
    {
        $this->id = $id;
    }

    public static function fromString(string $id): self
    {
        return new self($id);
    }

    public function __toString(): string
    {
        return $this->id;
    }

    public function equals(ProductId $other): bool
    {
        return $this->id === (string) $other;
    }
}
