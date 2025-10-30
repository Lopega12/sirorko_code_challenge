<?php

namespace App\Auth\Application\Http\DTO;

use Symfony\Component\HttpFoundation\Request;

final class LoginRequest
{
    public string $email = '';
    public string $password = '';

    public static function fromRequest(Request $request): self
    {
        $data = json_decode($request->getContent() ?? '{}', true);
        $dto = new self();
        $dto->email = $data['email'] ?? '';
        $dto->password = $data['password'] ?? '';

        return $dto;
    }
}
