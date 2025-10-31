<?php

namespace App\Cart\Application\Http\Controller;

use App\Cart\Application\Exception\CartNotFoundException;
use App\Cart\Application\Exception\InvalidCartIdException;
use App\Cart\Application\Exception\UnauthorizedCartAccessException;
use App\Cart\Application\Service\CartResolver;
use App\Cart\Application\Service\CartSerializer;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final class GetCartController
{
    private CartResolver $resolver;
    private CartSerializer $serializer;

    public function __construct(CartResolver $resolver, CartSerializer $serializer)
    {
        $this->resolver = $resolver;
        $this->serializer = $serializer;
    }

    #[OA\Get(
        path: '/api/cart/',
        summary: 'Obtener carrito',
        description: 'Obtiene el carrito del usuario autenticado con todos sus items',
        security: [['bearerAuth' => []]],
        tags: ['Cart'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Carrito obtenido exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'string', example: 'cart-123'),
                        new OA\Property(property: 'userId', type: 'string', example: 'user-456', nullable: true),
                        new OA\Property(
                            property: 'items',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'product_id', type: 'string', example: 'prod-789'),
                                    new OA\Property(property: 'name', type: 'string', example: 'Product Name'),
                                    new OA\Property(property: 'price', type: 'number', format: 'float', example: 99.99),
                                    new OA\Property(property: 'quantity', type: 'integer', example: 2),
                                    new OA\Property(property: 'subtotal', type: 'number', format: 'float', example: 199.98),
                                ]
                            )
                        ),
                        new OA\Property(property: 'total', type: 'number', format: 'float', example: 199.98),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'ID de carrito inválido'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Acceso no autorizado al carrito'),
            new OA\Response(response: 404, description: 'Carrito no encontrado'),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $cartId = $request->attributes->get('cartId') ?? $request->query->get('cart_id');

        try {
            $resolved = $this->resolver->resolve($cartId);
            $cart = $resolved['cart'];

            return new JsonResponse($this->serializer->serializeCart($cart));
        } catch (InvalidCartIdException $e) {
            return new JsonResponse(['error' => 'invalid_cart_id'], Response::HTTP_BAD_REQUEST);
        } catch (CartNotFoundException $e) {
            return new JsonResponse(['error' => 'cart_not_found'], Response::HTTP_NOT_FOUND);
        } catch (UnauthorizedCartAccessException $e) {
            $msg = $e->getMessage();
            if ('forbidden' === $msg) {
                return new JsonResponse(['error' => 'forbidden'], Response::HTTP_FORBIDDEN);
            }

            return new JsonResponse(['error' => 'unauthenticated'], Response::HTTP_UNAUTHORIZED);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'internal_error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
