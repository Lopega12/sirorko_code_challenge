<?php

namespace App\Cart\Application\Http\Controller;

use App\Cart\Application\Command\CheckoutCartCommand;
use App\Cart\Application\Handler\CheckoutCartHandler;
use App\Cart\Domain\Port\CartRepositoryInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[AsController]
final class CheckoutCartController
{
    use ResolveCartTrait;

    private CheckoutCartHandler $handler;
    private TokenStorageInterface $tokenStorage;
    private CartRepositoryInterface $cartRepository;

    public function __construct(CheckoutCartHandler $handler, TokenStorageInterface $tokenStorage, CartRepositoryInterface $cartRepository)
    {
        $this->handler = $handler;
        $this->tokenStorage = $tokenStorage;
        $this->cartRepository = $cartRepository;
    }

    protected function getTokenStorage(): TokenStorageInterface
    {
        return $this->tokenStorage;
    }

    protected function getCartRepository(): CartRepositoryInterface
    {
        return $this->cartRepository;
    }

    #[OA\Post(
        path: '/api/cart/checkout',
        summary: 'Realizar checkout del carrito',
        description: 'Convierte el carrito en una orden y procesa el pago de forma asíncrona',
        security: [['bearerAuth' => []]],
        tags: ['Cart'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Checkout iniciado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'order_id', type: 'string', example: 'order-123'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Carrito vacío o datos inválidos'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Acceso denegado'),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $cartId = $request->attributes->get('cartId') ?? $request->query->get('cart_id');

        try {
            $userId = $this->resolveUserIdFromParamInternal($cartId);

            $command = new CheckoutCartCommand($userId);
            $orderId = ($this->handler)($command);

            return new JsonResponse(['order_id' => (string) $orderId]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $e) {
            $msg = $e->getMessage();
            if ('unauthenticated' === $msg) {
                return new JsonResponse(['error' => $msg], Response::HTTP_UNAUTHORIZED);
            }
            if ('forbidden' === $msg) {
                return new JsonResponse(['error' => $msg], Response::HTTP_FORBIDDEN);
            }

            return new JsonResponse(['error' => 'internal_error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
