<?php

namespace App\Cart\Application\Handler;

use App\Cart\Application\Command\RemoveItemFromCartCommand;
use App\Cart\Domain\Cart;
use App\Cart\Domain\Port\CartRepositoryInterface;

final class RemoveItemFromCartHandler
{
    private CartRepositoryInterface $repository;

    public function __construct(CartRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function __invoke(RemoveItemFromCartCommand $command): void
    {
        $cart = $this->repository->findByUserId($command->userId) ?? Cart::createForUser($command->userId);
        $cart->removeItem($command->productId);

        $this->repository->save($cart);
    }
}
