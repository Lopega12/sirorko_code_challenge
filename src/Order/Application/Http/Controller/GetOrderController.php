<?php

namespace App\Order\Application\Http\Controller;

use App\Auth\Domain\User;
use App\Order\Application\Handler\GetOrderHandler;
use App\Order\Application\Query\GetOrderQuery;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class GetOrderController extends AbstractController
{
    public function __construct(
        private readonly GetOrderHandler $handler,
    ) {
    }

    #[OA\Get(
        path: '/api/orders/{orderId}',
        summary: 'Get order details',
        tags: ['Orders']
    )]
    #[OA\Parameter(
        name: 'orderId',
        description: 'Order ID',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', format: 'uuid')
    )]
    #[OA\Response(
        response: 200,
        description: 'Order details',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'id', type: 'string', example: '550e8400-e29b-41d4-a716-446655440000'),
                new OA\Property(property: 'cartId', type: 'string', example: '550e8400-e29b-41d4-a716-446655440001'),
                new OA\Property(property: 'status', type: 'string', example: 'paid'),
                new OA\Property(property: 'total', type: 'number', example: 299.99),
                new OA\Property(property: 'paymentReference', type: 'string', example: 'pi_1234567890', nullable: true),
                new OA\Property(
                    property: 'items',
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'productId', type: 'string'),
                            new OA\Property(property: 'name', type: 'string'),
                            new OA\Property(property: 'price', type: 'number'),
                            new OA\Property(property: 'quantity', type: 'integer'),
                        ],
                        type: 'object'
                    )
                ),
            ]
        )
    )]
    #[OA\Response(response: 404, description: 'Order not found')]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    public function __invoke(
        string $orderId,
        #[CurrentUser] ?User $user,
    ): JsonResponse {
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $query = new GetOrderQuery($orderId, $user->getId());
        $order = ($this->handler)($query);

        if (!$order) {
            return new JsonResponse(['error' => 'Order not found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'id' => (string) $order->id(),
            'cartId' => (string) $order->cartId(),
            'status' => $order->status()->value,
            'statusDescription' => $order->status()->description(),
            'total' => $order->total()->toFloat(),
            'paymentReference' => $order->paymentReference(),
            'items' => array_map(function ($item) {
                return [
                    'productId' => (string) $item->productId(),
                    'name' => $item->name(),
                    'price' => $item->price()->toFloat(),
                    'quantity' => $item->quantity(),
                    'subtotal' => $item->total()->toFloat(),
                ];
            }, $order->items()),
        ]);
    }
}
