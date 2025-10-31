<?php

namespace App\Cart\Infrastructure;

use App\Cart\Domain\Cart;
use App\Cart\Domain\CartId;
use App\Cart\Domain\CartItem;
use App\Cart\Domain\CartRepositoryInterface;
use App\Cart\Domain\Money as CartMoney;
use App\Cart\Domain\ProductId as DomainProductId;
use App\Cart\Infrastructure\Entity\CartEntity;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineCartRepository implements CartRepositoryInterface
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function save(Cart $cart): void
    {
        $items = array_map(function (CartItem $i) {
            return [
                'product_id' => (string) $i->productId(),
                'name' => $i->name(),
                'price' => $i->price()->toFloat(),
                'quantity' => $i->quantity(),
            ];
        }, $cart->items());

        $existing = $this->em->find(CartEntity::class, (string) $cart->id());
        if ($existing) {
            $existing->setItems($items);
            $existing->setUserId($cart->userId());
            $this->em->persist($existing);
        } else {
            $entity = new CartEntity((string) $cart->id(), $items, $cart->userId());
            $this->em->persist($entity);
        }

        $this->em->flush();
    }

    public function get(CartId $id): ?Cart
    {
        $entity = $this->em->find(CartEntity::class, (string) $id);
        if (!$entity) {
            return null;
        }

        $items = [];
        foreach ($entity->getItems() as $i) {
            $items[] = new CartItem(DomainProductId::fromString($i['product_id']), $i['name'], CartMoney::fromFloat((float) $i['price']), (int) $i['quantity']);
        }

        $cart = new Cart(CartId::fromString($entity->getId()), $entity->getUserId());
        foreach ($items as $item) {
            $cart->addItem($item);
        }

        return $cart;
    }

    public function findByUserId(string $userId): ?Cart
    {
        $repo = $this->em->getRepository(CartEntity::class);
        $entity = $repo->findOneBy(['userId' => $userId]);
        if (!$entity) {
            return null;
        }

        $items = [];
        foreach ($entity->getItems() as $i) {
            $items[] = new CartItem(DomainProductId::fromString($i['product_id']), $i['name'], CartMoney::fromFloat((float) $i['price']), (int) $i['quantity']);
        }

        $cart = new Cart(CartId::fromString($entity->getId()), $entity->getUserId());
        foreach ($items as $item) {
            $cart->addItem($item);
        }

        return $cart;
    }
}
