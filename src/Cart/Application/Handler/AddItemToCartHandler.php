<?php

namespace App\Cart\Application\Handler;

use App\Cart\Application\Command\AddItemToCartCommand;
use App\Cart\Domain\Cart;
use App\Cart\Domain\CartItem;
use App\Cart\Domain\CartRepositoryInterface;
use App\Product\Domain\Repository\ProductRepositoryInterface;

final class AddItemToCartHandler
{
    private CartRepositoryInterface $repository;
    private ProductRepositoryInterface $productRepository;

    public function __construct(CartRepositoryInterface $repository, ProductRepositoryInterface $productRepository)
    {
        $this->repository = $repository;
        $this->productRepository = $productRepository;
    }

    public function __invoke(AddItemToCartCommand $command): void
    {
        $product = $this->productRepository->find((string) $command->productId);
        if (!$product) {
            throw new \InvalidArgumentException('Product not found');
        }

        $cart = $this->repository->findByUserId($command->userId) ?? Cart::createForUser($command->userId);

        // build cart item from product snapshot
        $item = new CartItem(
            $command->productId,
            $product->getName(),
            // product domain returns Money object (Product ValueObject)
            // Cart uses its own Money VO; convert via cents/float
            \App\Cart\Domain\Money::fromFloat($product->getPrice()->amount()),
            $command->quantity
        );

        $cart->addItem($item);

        $this->repository->save($cart);
    }
}
