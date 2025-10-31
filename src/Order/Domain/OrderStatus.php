<?php

namespace App\Order\Domain;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case PAID = 'paid';
    case PAYMENT_FAILED = 'payment_failed';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function isPending(): bool
    {
        return self::PENDING === $this;
    }

    public function isProcessing(): bool
    {
        return self::PROCESSING === $this;
    }

    public function isPaid(): bool
    {
        return self::PAID === $this;
    }

    public function hasPaymentFailed(): bool
    {
        return self::PAYMENT_FAILED === $this;
    }

    public function isCompleted(): bool
    {
        return self::COMPLETED === $this;
    }

    public function isCancelled(): bool
    {
        return self::CANCELLED === $this;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this, [self::PENDING, self::PAYMENT_FAILED], true);
    }

    public function canBeProcessed(): bool
    {
        return self::PENDING === $this;
    }

    public function description(): string
    {
        return match ($this) {
            self::PENDING => 'Pedido pendiente de procesamiento',
            self::PROCESSING => 'Procesando pago',
            self::PAID => 'Pago completado',
            self::PAYMENT_FAILED => 'Fallo en el pago',
            self::COMPLETED => 'Pedido completado',
            self::CANCELLED => 'Pedido cancelado',
        };
    }
}
