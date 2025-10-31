<?php

namespace App\Cart\Application\Service;

use App\Cart\Application\Exception\CartNotFoundException;
use App\Cart\Application\Exception\InvalidCartIdException;
use App\Cart\Application\Exception\UnauthorizedCartAccessException;
use App\Cart\Domain\CartId;
use App\Cart\Domain\Port\CartRepositoryInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class CartResolver
{
    private CartRepositoryInterface $carts;
    private Security $security;

    public function __construct(CartRepositoryInterface $carts, Security $security)
    {
        $this->carts = $carts;
        $this->security = $security;
    }

    /**
     *  Resuelve el carrito basado en el ID proporcionado,o el carrito del usuario autenticado si no se proporciona un ID explícito.
     *
     * @return array<mixed>
     *
     * @throws invalidCartIdException, CartNotFoundException, UnauthorizedCartAccessException
     * Throws exceptions for invalid id / not found / unauthorized
     */
    public function resolve(?string $cartId): array
    {
        $explicit = null !== $cartId && '' !== $cartId && 'me' !== $cartId && 'current' !== $cartId;
        $user = $this->security->getUser();
        $authUserId = $user && method_exists($user, 'getId') ? $user->getId() : null;

        if ($explicit) {
            try {
                $id = CartId::fromString($cartId);
            } catch (\InvalidArgumentException $e) {
                throw new InvalidCartIdException('invalid_cart_id');
            }

            $cart = $this->carts->get($id);
            if (!$cart) {
                throw new CartNotFoundException('cart_not_found');
            }

            $ownerId = $cart->userId();
            if ($ownerId) {
                if (!$authUserId) {
                    throw new UnauthorizedCartAccessException('unauthenticated');
                }
                if ($authUserId !== $ownerId) {
                    throw new UnauthorizedCartAccessException('forbidden');
                }
            }

            return ['cart' => $cart, 'explicit' => true];
        }

        // En caso de acceso implícito, se requiere autenticación
        if (!$authUserId) {
            throw new UnauthorizedCartAccessException('unauthenticated');
        }

        $cart = $this->carts->findByUserId($authUserId);

        return ['cart' => $cart, 'explicit' => false];
    }
}
