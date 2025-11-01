<?php

namespace App\Cart\Application\Http\Controller;

use App\Cart\Application\Command\RemoveItemFromCartCommand;
use App\Cart\Application\Handler\RemoveItemFromCartHandler;
use App\Cart\Domain\Port\CartRepositoryInterface;
use App\Cart\Domain\ProductId;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[AsController]
final class RemoveItemController
{
    use ResolveCartTrait;

    private RemoveItemFromCartHandler $handler;
    private TokenStorageInterface $tokenStorage;
    private CartRepositoryInterface $cartRepository;

    public function __construct(RemoveItemFromCartHandler $handler, TokenStorageInterface $tokenStorage, CartRepositoryInterface $cartRepository)
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

    #[OA\Delete(
        path: '/api/cart/items/{productId}',
        summary: 'Eliminar producto del carrito',
        description: 'Elimina completamente un producto del carrito',
        security: [['bearerAuth' => []]],
        tags: ['Cart'],
        parameters: [
            new OA\Parameter(
                name: 'productId',
                in: 'path',
                required: true,
                description: 'ID del producto a eliminar',
                schema: new OA\Schema(type: 'string', example: 'prod-123')
            ),
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Producto eliminado',
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

            $command = new RemoveItemFromCartCommand($userId, ProductId::fromString($productId));
            ($this->handler)($command);

            return new JsonResponse([], Response::HTTP_NO_CONTENT);
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
