<?php

namespace App\Order\Infrastructure;

use App\Cart\Domain\CartItem;
use App\Cart\Domain\Money as CartMoney;
use App\Cart\Domain\ProductId as DomainProductId;
use App\Order\Domain\Order as DomainOrder;
use App\Order\Domain\OrderId as DomainOrderId;
use App\Order\Domain\OrderRepositoryInterface as DomainOrderRepositoryInterface;
use App\Order\Infrastructure\Entity\OrderEntity;
use App\Order\Infrastructure\Entity\OrderItemEntity;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineOrderRepository implements DomainOrderRepositoryInterface
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function save(DomainOrder $order): void
    {
        $entity = $this->em->find(OrderEntity::class, (string) $order->id());

        if ($entity) {
            // Actualizar orden existente
            $entity->setStatus($order->status()->value);
            if ($order->paymentReference()) {
                $entity->setPaymentReference($order->paymentReference());
            }
        } else {
            // Crear nueva orden
            $entity = new OrderEntity(
                (string) $order->id(),
                (string) $order->cartId(),
                $order->total()->toFloat(),
                $order->status()->value
            );

            if ($order->paymentReference()) {
                $entity->setPaymentReference($order->paymentReference());
            }

            foreach ($order->items() as $i) {
                $itemEntity = new OrderItemEntity(
                    $entity,
                    (string) $i->productId(),
                    $i->name(),
                    $i->price()->toFloat(),
                    $i->quantity()
                );
                $entity->addItem($itemEntity);
                $this->em->persist($itemEntity);
            }

            $this->em->persist($entity);
        }

        $this->em->flush();
    }

    public function get(DomainOrderId $id): ?DomainOrder
    {
        $entity = $this->em->find(OrderEntity::class, (string) $id);
        if (!$entity) {
            return null;
        }

        $items = [];
        foreach ($entity->getItems() as $i) {
            $items[] = new CartItem(
                DomainProductId::fromString($i->getProductId()),
                $i->getName(),
                CartMoney::fromFloat((float) $i->getPrice()),
                (int) $i->getQuantity()
            );
        }

        return new DomainOrder(
            DomainOrderId::fromString($entity->getId()),
            \App\Cart\Domain\CartId::fromString($entity->getCartId()),
            $items,
            CartMoney::fromFloat($entity->getTotal()),
            \App\Order\Domain\OrderStatus::from($entity->getStatus()),
            $entity->getPaymentReference()
        );
    }
}
