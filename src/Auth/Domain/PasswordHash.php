<?php

namespace App\Auth\Domain;

final class PasswordHash
{
    private string $hash;

    private function __construct(string $hash)
    {
        if ('' === $hash) {
            throw new \InvalidArgumentException('Hash no puede estar vacío');
        }
        $this->hash = $hash;
    }

    public static function fromHash(string $hash): self
    {
        return new self($hash);
    }

    public static function fromPlain(string $plain, array $options = []): self
    {
        $hashed = password_hash($plain, PASSWORD_DEFAULT, $options);
        if (false === $hashed) {
            throw new \RuntimeException('No se pudo generar el hash de la contraseña');
        }

        return new self($hashed);
    }

    public function verify(string $plain): bool
    {
        return password_verify($plain, $this->hash);
    }

    public function needsRehash(array $options = []): bool
    {
        return password_needs_rehash($this->hash, PASSWORD_DEFAULT, $options);
    }

    public function value(): string
    {
        return $this->hash;
    }

    public function equals(self $other): bool
    {
        return hash_equals($this->hash, $other->value());
    }
}
