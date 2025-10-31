<?php

namespace App\Tests\Factory;

use App\Cart\Domain\CartId;
use App\Cart\Domain\Money;
use App\Order\Domain\OrderId;
use App\Order\Domain\OrderStatus;
use App\Order\Infrastructure\Entity\OrderEntity;
use App\Order\Infrastructure\Entity\OrderItemEntity;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

final class OrderFactory extends PersistentObjectFactory
{
    protected function instantiate(array $attributes): object
    {
        // Extract ID as string
        $orderId = $attributes['id'] ?? OrderId::generate();
        if ($orderId instanceof OrderId) {
            $orderId = (string) $orderId;
        } elseif (!is_string($orderId)) {
            $orderId = (string) OrderId::generate();
        }

        // Extract CartId as string
        $cartId = $attributes['cartId'] ?? CartId::generate();
        if ($cartId instanceof CartId) {
            $cartId = (string) $cartId;
        } elseif (!is_string($cartId)) {
            $cartId = (string) CartId::generate();
        }

        // Items
        $items = $attributes['items'] ?? [];

        // Total
        $total = $attributes['total'] ?? null;
        if ($total instanceof Money) {
            $total = $total->toFloat();
        } elseif ($total === null && !empty($items)) {
            // Calculate from items
            $totalCents = 0;
            foreach ($items as $item) {
                $totalCents += $item->price()->toCents() * $item->quantity();
            }
            $total = $totalCents / 100;
        } else {
            $total = 100.0; // Default
        }

        // Status
        $status = $attributes['status'] ?? OrderStatus::PENDING;
        if ($status instanceof OrderStatus) {
            $status = $status->value;
        }

        // Payment reference
        $paymentReference = $attributes['paymentReference'] ?? null;

        // Create OrderEntity (la entidad Doctrine) - todos los parámetros como strings/primitivos
        $orderEntity = new OrderEntity($orderId, $cartId, $total, $status);

        if ($paymentReference) {
            $orderEntity->setPaymentReference($paymentReference);
        }

        // Add items if provided
        foreach ($items as $item) {
            $itemEntity = new OrderItemEntity(
                $orderEntity,
                (string) $item->productId(),
                $item->name(),
                $item->price()->toFloat(),
                $item->quantity()
            );
            $orderEntity->addItem($itemEntity);
        }

        return $orderEntity;
    }

    protected function defaults(): array|callable
    {
        return [
            'status' => OrderStatus::PENDING->value,
        ];
    }

    public static function class(): string
    {
        return OrderEntity::class;
    }

    protected function initialize(): static
    {
        return $this;
    }
}

