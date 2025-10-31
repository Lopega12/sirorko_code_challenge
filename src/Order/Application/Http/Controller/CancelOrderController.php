<?php

namespace App\Order\Application\Http\Controller;

use App\Auth\Domain\User;
use App\Order\Application\Command\CancelOrderCommand;
use App\Order\Application\Handler\CancelOrderHandler;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class CancelOrderController extends AbstractController
{
    public function __construct(
        private readonly CancelOrderHandler $handler,
    ) {
    }

    #[OA\Post(
        path: '/api/orders/{orderId}/cancel',
        summary: 'Cancel an order',
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
        description: 'Order cancelled successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Order cancelled successfully'),
            ]
        )
    )]
    #[OA\Response(response: 400, description: 'Cannot cancel order in current status')]
    #[OA\Response(response: 404, description: 'Order not found')]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    public function __invoke(
        string $orderId,
        #[CurrentUser] ?User $user,
    ): JsonResponse {
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $command = new CancelOrderCommand($orderId, $user->getId());
            ($this->handler)($command);

            return new JsonResponse([
                'message' => 'Order cancelled successfully',
            ]);
        } catch (\DomainException $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'error' => 'Order not found',
            ], Response::HTTP_NOT_FOUND);
        }
    }
}
