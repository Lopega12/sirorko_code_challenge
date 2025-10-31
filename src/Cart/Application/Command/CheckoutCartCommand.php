<?php

namespace App\Cart\Application\Command;

final class CheckoutCartCommand
{
    public string $userId;

    public function __construct(string $userId)
    {
        $this->userId = $userId;
    }
}
