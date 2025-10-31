<?php

namespace App\Cart\Application\Handler;

use App\Cart\Application\Command\UpdateItemQuantityCommand;
use App\Cart\Domain\Cart;
use App\Cart\Domain\CartRepositoryInterface;

final class UpdateItemQuantityHandler
{
    private CartRepositoryInterface $repository;

    public function __construct(CartRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function __invoke(UpdateItemQuantityCommand $command): void
    {
        $cart = $this->repository->findByUserId($command->userId) ?? Cart::createForUser($command->userId);
        $cart->updateItemQuantity($command->productId, $command->quantity);

        $this->repository->save($cart);
    }
}
