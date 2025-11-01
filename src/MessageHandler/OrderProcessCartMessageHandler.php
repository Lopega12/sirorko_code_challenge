<?php

namespace App\MessageHandler;

use App\Cart\Domain\Port\CartRepositoryInterface;
use App\Message\Order\OrderProcessCartMessage;
use App\Order\Domain\Event\OrderCreated;
use App\Order\Domain\Order;
use App\Order\Domain\OrderId;
use App\Order\Domain\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler]
final class OrderProcessCartMessageHandler
{
    public function __construct(
        private readonly CartRepositoryInterface $cartRepository,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(OrderProcessCartMessage $message): void
    {
        $orderId = OrderId::fromString($message->orderId());

        $this->logger->info('Processing cart order - starting payment flow', [
            'order_id' => (string) $orderId,
        ]);

        try {
            // 1. Buscar la orden existente para obtener el cartId
            $order = $this->orderRepository->get($orderId);

            if (!$order) {
                $this->logger->error('Order not found for processing', [
                    'order_id' => (string) $orderId,
                ]);

                return;
            }

            // 2. Obtener el carrito asociado
            $cart = $this->cartRepository->get($order->cartId());

            if (!$cart) {
                $this->logger->error('Cart not found for order', [
                    'order_id' => (string) $orderId,
                    'cart_id' => (string) $order->cartId(),
                ]);

                return;
            }

            // 3. Verificar que el carrito tenga items
            if (empty($cart->items())) {
                $this->logger->warning('Cart is empty, cannot process order', [
                    'order_id' => (string) $orderId,
                    'cart_id' => (string) $cart->id(),
                ]);

                return;
            }

            // 4. Validar stock de productos (opcional pero recomendado)
            $this->logger->info('Validating product stock', [
                'order_id' => (string) $orderId,
            ]);
            // TODO: Implementar validación de stock con ProductRepository
            // foreach ($cart->items() as $item) {
            //     $product = $this->productRepository->get($item->productId());
            //     if (!$product->hasStock($item->quantity())) {
            //         throw new InsufficientStockException();
            //     }
            // }

            // 5. Marcar orden como procesando
            $order->markAsProcessing();
            $this->orderRepository->save($order);

            $this->logger->info('Order marked as processing, initiating payment', [
                'order_id' => (string) $orderId,
                'amount' => $order->total()->toFloat(),
            ]);

            // TODO: Aqui iria la Integracion con servicio de pago (Stripe, PayPal, etc.)
            try {
                // $paymentResult = $this->paymentService->charge(
                //     amount: $order->total(),
                //     orderId: (string) $orderId,
                //     customerId: $cart->userId(),
                //     description: "Order {$orderId}"
                // );

                // if (!$paymentResult->isSuccessful()) {
                //     $order->markAsPaymentFailed();
                //     $this->orderRepository->save($order);
                //
                //     $this->logger->error('Payment failed', [
                //         'order_id' => (string) $orderId,
                //         'reason' => $paymentResult->getErrorMessage()
                //     ]);
                //
                //     throw new \Exception('Payment failed: ' . $paymentResult->getErrorMessage());
                // }

                // Simulación de proceso de pago exitoso (ELIMINAR cuando tengas servicio real)
                $simulatedPaymentRef = 'sim_'.uniqid();
                $order->markAsPaid($simulatedPaymentRef);
                $this->orderRepository->save($order);

                $this->logger->info('Payment processed successfully (simulated)', [
                    'order_id' => (string) $orderId,
                    'amount' => $order->total()->toFloat(),
                    'payment_reference' => $simulatedPaymentRef,
                ]);
            } catch (\Exception $e) {
                // Si falla el pago, marcar como payment_failed
                $order->markAsPaymentFailed();
                $this->orderRepository->save($order);
                throw $e;
            }

            // 6. Actualizar inventario (reducir stock)
            $this->logger->info('Updating product inventory', [
                'order_id' => (string) $orderId,
            ]);
            // TODO: Implementar reducción de stock
            // foreach ($cart->items() as $item) {
            //     $this->productRepository->reduceStock(
            //         $item->productId(),
            //         $item->quantity()
            //     );
            // }

            // 7. Disparar el evento OrderCreated para procesamiento adicional
            $this->eventDispatcher->dispatch(new OrderCreated(
                $orderId,
                $cart->id(),
                $order->total()
            ));

            // 8. Vaciar o marcar el carrito como procesado (opcional)
            // $this->cartRepository->clearCart($cart->id());

            $this->logger->info('Order processed successfully with payment', [
                'order_id' => (string) $orderId,
                'cart_id' => (string) $cart->id(),
                'total' => $order->total()->toFloat(),
                'items_count' => count($cart->items()),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error processing cart order', [
                'order_id' => (string) $orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // El mensaje será reenviado automáticamente por Messenger
            // según la configuración de retry_strategy
            throw $e;
        }
    }
}
