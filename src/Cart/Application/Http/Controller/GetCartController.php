<?php

namespace App\Cart\Application\Http\Controller;

use App\Cart\Domain\CartId;
use App\Cart\Domain\CartRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;

final class GetCartController
{
    use ResolveCartTrait;

    private Security $security;
    private CartRepositoryInterface $cartRepository;

    public function __construct(Security $security, CartRepositoryInterface $cartRepository)
    {
        $this->security = $security;
        $this->cartRepository = $cartRepository;
    }

    protected function getSecurity(): Security
    {
        return $this->security;
    }

    protected function getCartRepository(): CartRepositoryInterface
    {
        return $this->cartRepository;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $cartId = $request->attributes->get('cartId') ?? $request->query->get('cart_id');

        try {
            if (!$cartId || 'me' === $cartId || 'current' === $cartId) {
                $userId = $this->getUserIdFromSecurity();
                if (!$userId) {
                    return new JsonResponse(['items' => [], 'total' => 0]);
                }

                $cart = $this->cartRepository->findByUserId($userId);
            } else {
                $cart = $this->cartRepository->get(CartId::fromString($cartId));
            }

            if (!$cart) {
                return new JsonResponse(['items' => [], 'total' => 0]);
            }

            $items = array_map(function ($i) {
                return [
                    'product_id' => (string) $i->productId(),
                    'name' => $i->name(),
                    'price' => $i->price()->toFloat(),
                    'quantity' => $i->quantity(),
                    'total' => $i->total()->toFloat(),
                ];
            }, $cart->items());

            return new JsonResponse(['items' => $items, 'total' => $cart->total()->toFloat()]);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'internal_error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
