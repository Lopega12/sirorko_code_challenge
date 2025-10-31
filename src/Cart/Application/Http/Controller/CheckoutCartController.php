<?php

namespace App\Cart\Application\Http\Controller;

use App\Cart\Application\Command\CheckoutCartCommand;
use App\Cart\Application\Handler\CheckoutCartHandler;
use App\Cart\Domain\CartRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;

final class CheckoutCartController
{
    use ResolveCartTrait;

    private CheckoutCartHandler $handler;
    private Security $security;
    private CartRepositoryInterface $cartRepository;

    public function __construct(CheckoutCartHandler $handler, Security $security, CartRepositoryInterface $cartRepository)
    {
        $this->handler = $handler;
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
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'internal_error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
