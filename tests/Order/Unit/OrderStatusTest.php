<?php

namespace App\Tests\Order\Unit;

use App\Order\Domain\OrderStatus;
use PHPUnit\Framework\TestCase;

class OrderStatusTest extends TestCase
{
    public function testAllStatusesHaveCorrectValues(): void
    {
        $this->assertEquals('pending', OrderStatus::PENDING->value);
        $this->assertEquals('processing', OrderStatus::PROCESSING->value);
        $this->assertEquals('paid', OrderStatus::PAID->value);
        $this->assertEquals('payment_failed', OrderStatus::PAYMENT_FAILED->value);
        $this->assertEquals('completed', OrderStatus::COMPLETED->value);
        $this->assertEquals('cancelled', OrderStatus::CANCELLED->value);
    }

    public function testIsPendingMethod(): void
    {
        $this->assertTrue(OrderStatus::PENDING->isPending());
        $this->assertFalse(OrderStatus::PROCESSING->isPending());
        $this->assertFalse(OrderStatus::PAID->isPending());
    }

    public function testIsProcessingMethod(): void
    {
        $this->assertTrue(OrderStatus::PROCESSING->isProcessing());
        $this->assertFalse(OrderStatus::PENDING->isProcessing());
        $this->assertFalse(OrderStatus::PAID->isProcessing());
    }

    public function testIsPaidMethod(): void
    {
        $this->assertTrue(OrderStatus::PAID->isPaid());
        $this->assertFalse(OrderStatus::PENDING->isPaid());
        $this->assertFalse(OrderStatus::PROCESSING->isPaid());
    }

    public function testHasPaymentFailedMethod(): void
    {
        $this->assertTrue(OrderStatus::PAYMENT_FAILED->hasPaymentFailed());
        $this->assertFalse(OrderStatus::PENDING->hasPaymentFailed());
        $this->assertFalse(OrderStatus::PAID->hasPaymentFailed());
    }

    public function testIsCompletedMethod(): void
    {
        $this->assertTrue(OrderStatus::COMPLETED->isCompleted());
        $this->assertFalse(OrderStatus::PAID->isCompleted());
        $this->assertFalse(OrderStatus::PENDING->isCompleted());
    }

    public function testIsCancelledMethod(): void
    {
        $this->assertTrue(OrderStatus::CANCELLED->isCancelled());
        $this->assertFalse(OrderStatus::PENDING->isCancelled());
        $this->assertFalse(OrderStatus::PAID->isCancelled());
    }

    public function testCanBeCancelledForPendingStatus(): void
    {
        $this->assertTrue(OrderStatus::PENDING->canBeCancelled());
    }

    public function testCanBeCancelledForPaymentFailedStatus(): void
    {
        $this->assertTrue(OrderStatus::PAYMENT_FAILED->canBeCancelled());
    }

    public function testCannotBeCancelledForPaidStatus(): void
    {
        $this->assertFalse(OrderStatus::PAID->canBeCancelled());
    }

    public function testCannotBeCancelledForProcessingStatus(): void
    {
        $this->assertFalse(OrderStatus::PROCESSING->canBeCancelled());
    }

    public function testCannotBeCancelledForCompletedStatus(): void
    {
        $this->assertFalse(OrderStatus::COMPLETED->canBeCancelled());
    }

    public function testCanBeProcessedForPendingStatus(): void
    {
        $this->assertTrue(OrderStatus::PENDING->canBeProcessed());
    }

    public function testCannotBeProcessedForNonPendingStatus(): void
    {
        $this->assertFalse(OrderStatus::PROCESSING->canBeProcessed());
        $this->assertFalse(OrderStatus::PAID->canBeProcessed());
        $this->assertFalse(OrderStatus::PAYMENT_FAILED->canBeProcessed());
        $this->assertFalse(OrderStatus::COMPLETED->canBeProcessed());
        $this->assertFalse(OrderStatus::CANCELLED->canBeProcessed());
    }

    public function testDescriptionsInSpanish(): void
    {
        $this->assertEquals('Pedido pendiente de procesamiento', OrderStatus::PENDING->description());
        $this->assertEquals('Procesando pago', OrderStatus::PROCESSING->description());
        $this->assertEquals('Pago completado', OrderStatus::PAID->description());
        $this->assertEquals('Fallo en el pago', OrderStatus::PAYMENT_FAILED->description());
        $this->assertEquals('Pedido completado', OrderStatus::COMPLETED->description());
        $this->assertEquals('Pedido cancelado', OrderStatus::CANCELLED->description());
    }

    public function testCanCreateFromString(): void
    {
        $this->assertEquals(OrderStatus::PENDING, OrderStatus::from('pending'));
        $this->assertEquals(OrderStatus::PROCESSING, OrderStatus::from('processing'));
        $this->assertEquals(OrderStatus::PAID, OrderStatus::from('paid'));
        $this->assertEquals(OrderStatus::PAYMENT_FAILED, OrderStatus::from('payment_failed'));
        $this->assertEquals(OrderStatus::COMPLETED, OrderStatus::from('completed'));
        $this->assertEquals(OrderStatus::CANCELLED, OrderStatus::from('cancelled'));
    }

    public function testThrowsExceptionForInvalidStatus(): void
    {
        $this->expectException(\ValueError::class);
        OrderStatus::from('invalid_status');
    }
}

