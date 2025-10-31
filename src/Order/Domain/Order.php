<?php

namespace App\Order\Domain;

use App\Cart\Domain\CartId;
use App\Cart\Domain\CartItem;
use App\Cart\Domain\Money;

final class Order
{
    private OrderId $id;
    private CartId $cartId;
    /** @var CartItem[] */
    private array $items;
    private Money $total;
    private OrderStatus $status;
    private ?string $paymentReference = null;

    public function __construct(
        OrderId $id,
        CartId $cartId,
        array $items,
        Money $total,
        OrderStatus $status = OrderStatus::PENDING,
        ?string $paymentReference = null,
    ) {
        $this->id = $id;
        $this->cartId = $cartId;
        $this->items = $items;
        $this->total = $total;
        $this->status = $status;
        $this->paymentReference = $paymentReference;
    }

    public function id(): OrderId
    {
        return $this->id;
    }

    public function cartId(): CartId
    {
        return $this->cartId;
    }

    public function items(): array
    {
        return $this->items;
    }

    public function total(): Money
    {
        return $this->total;
    }

    public function status(): OrderStatus
    {
        return $this->status;
    }

    public function paymentReference(): ?string
    {
        return $this->paymentReference;
    }

    public function markAsProcessing(): void
    {
        if (!$this->status->canBeProcessed()) {
            throw new \DomainException("Cannot process order in status: {$this->status->value}");
        }
        $this->status = OrderStatus::PROCESSING;
    }

    public function markAsPaid(string $paymentReference): void
    {
        if (!$this->status->isProcessing()) {
            throw new \DomainException("Cannot mark as paid order in status: {$this->status->value}");
        }
        $this->status = OrderStatus::PAID;
        $this->paymentReference = $paymentReference;
    }

    public function markAsPaymentFailed(): void
    {
        if (!$this->status->isProcessing()) {
            throw new \DomainException("Cannot mark payment failed for order in status: {$this->status->value}");
        }
        $this->status = OrderStatus::PAYMENT_FAILED;
    }

    public function markAsCompleted(): void
    {
        if (!$this->status->isPaid()) {
            throw new \DomainException("Cannot complete order in status: {$this->status->value}");
        }
        $this->status = OrderStatus::COMPLETED;
    }

    public function cancel(): void
    {
        if (!$this->status->canBeCancelled()) {
            throw new \DomainException("Cannot cancel order in status: {$this->status->value}");
        }
        $this->status = OrderStatus::CANCELLED;
    }
}
