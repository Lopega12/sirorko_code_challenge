<?php

namespace App\Cart\Application\Http\Controller;

use App\Cart\Application\Command\UpdateItemQuantityCommand;
use App\Cart\Application\Handler\UpdateItemQuantityHandler;
use App\Cart\Domain\Port\CartRepositoryInterface;
use App\Cart\Domain\ProductId;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[AsController]
final class UpdateItemController
{
    use ResolveCartTrait;

    private UpdateItemQuantityHandler $handler;
    private TokenStorageInterface $tokenStorage;
    private CartRepositoryInterface $cartRepository;

    public function __construct(UpdateItemQuantityHandler $handler, TokenStorageInterface $tokenStorage, CartRepositoryInterface $cartRepository)
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

    #[OA\Put(
        path: '/api/cart/items/{productId}',
        summary: 'Actualizar cantidad de producto en carrito',
        description: 'Actualiza la cantidad de un producto específico en el carrito',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['quantity'],
                properties: [
                    new OA\Property(property: 'quantity', type: 'integer', example: 3),
                ]
            )
        ),
        tags: ['Cart'],
        parameters: [
            new OA\Parameter(
                name: 'productId',
                in: 'path',
                required: true,
                description: 'ID del producto',
                schema: new OA\Schema(type: 'string', example: 'prod-123')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Cantidad actualizada',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'ok'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Datos inválidos'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Acceso denegado'),
        ]
    )]
    #[OA\Patch(
        path: '/api/cart/items/{productId}',
        summary: 'Actualizar cantidad de producto en carrito (PATCH)',
        description: 'Actualiza la cantidad de un producto específico en el carrito',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['quantity'],
                properties: [
                    new OA\Property(property: 'quantity', type: 'integer', example: 3),
                ]
            )
        ),
        tags: ['Cart'],
        parameters: [
            new OA\Parameter(
                name: 'productId',
                in: 'path',
                required: true,
                description: 'ID del producto',
                schema: new OA\Schema(type: 'string', example: 'prod-123')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Cantidad actualizada',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'ok'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Datos inválidos'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Acceso denegado'),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $cartId = $request->attributes->get('cartId') ?? $request->query->get('cart_id');
        $productId = $request->attributes->get('productId');

        try {
            $userId = $this->resolveUserIdFromParamInternal($cartId);

            $data = json_decode($request->getContent(), true);
            $quantity = (int) ($data['quantity'] ?? 0);
            if ($quantity < 0) {
                return new JsonResponse(['error' => 'quantity must be >= 0'], Response::HTTP_BAD_REQUEST);
            }

            $command = new UpdateItemQuantityCommand($userId, ProductId::fromString($productId), $quantity);
            ($this->handler)($command);

            return new JsonResponse(['status' => 'ok']);
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
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'internal_error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
