<?php

namespace App\Cart\Application\Http\Controller;

use App\Cart\Domain\CartId;
use App\Cart\Domain\Port\CartRepositoryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

trait ResolveCartTrait
{
    abstract protected function getTokenStorage(): TokenStorageInterface;

    abstract protected function getCartRepository(): CartRepositoryInterface;

    private function getUserIdFromTokenStorage(): ?string
    {
        $token = $this->getTokenStorage()->getToken();
        if (!$token) {
            return null;
        }

        $user = $token->getUser();
        if (!$user) {
            return null;
        }

        return method_exists($user, 'getId') ? $user->getId() : null;
    }

    private function resolveUserIdFromParamInternal(?string $cartId): string
    {
        $authUserId = $this->getUserIdFromTokenStorage();

        if (!$cartId || 'me' === $cartId || 'current' === $cartId) {
            if (!$authUserId) {
                throw new \RuntimeException('unauthenticated');
            }

            return $authUserId;
        }

        try {
            $cartUuid = CartId::fromString($cartId);
        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException('Invalid cart id');
        }

        $cart = $this->getCartRepository()->get($cartUuid);
        if (!$cart) {
            throw new \InvalidArgumentException('Cart not found');
        }

        $ownerId = $cart->userId();
        if ($ownerId) {
            if ($authUserId && $authUserId !== $ownerId) {
                throw new \RuntimeException('forbidden');
            }

            if (!$authUserId) {
                throw new \RuntimeException('unauthenticated');
            }

            return $ownerId;
        }

        if (!$authUserId) {
            throw new \RuntimeException('unauthenticated');
        }

        return $authUserId;
    }
}
