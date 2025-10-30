<?php

namespace App\Auth\Domain;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
final class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private string $email;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(type: 'string')]
    private string $password;

    public function __construct(string $email, string $passwordHash, ?string $id = null, array $roles = ['ROLE_USER'])
    {
        $this->id = $id ?? Uuid::uuid4()->toString();
        $this->email = $email;
        $this->password = $passwordHash;
        $this->roles = $roles;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    // Value Object accessor
    public function getEmailVO(): Email
    {
        return Email::fromString($this->email);
    }

    public function setEmailFromVO(Email $email): void
    {
        $this->email = $email->value();
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        if (!in_array('ROLE_USER', $roles, true)) {
            $roles[] = 'ROLE_USER';
        }

        return array_values($roles);
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $hash): void
    {
        $this->password = $hash;
    }

    public function getPasswordHashVO(): PasswordHash
    {
        return PasswordHash::fromHash($this->password);
    }

    public function setPasswordHashFromVO(PasswordHash $ph): void
    {
        $this->password = $ph->value();
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // No sensitive data to erase
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'roles' => $this->roles,
        ];
    }
}
