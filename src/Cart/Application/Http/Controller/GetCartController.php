<?php

namespace App\Cart\Application\Http\Controller;

use App\Cart\Application\Exception\CartNotFoundException;
use App\Cart\Application\Exception\InvalidCartIdException;
use App\Cart\Application\Exception\UnauthorizedCartAccessException;
use App\Cart\Application\Service\CartResolver;
use App\Cart\Application\Service\CartSerializer;
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
