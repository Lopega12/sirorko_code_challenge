<?php

namespace App\Cart\Application\Http\Controller;

use App\Cart\Application\Command\AddItemToCartCommand;
use App\Cart\Application\Handler\AddItemToCartHandler;
use App\Cart\Domain\CartRepositoryInterface;
use App\Cart\Domain\ProductId;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;

final class AddItemController
{
    use ResolveCartTrait;

    private AddItemToCartHandler $handler;
    private Security $security;
    private CartRepositoryInterface $cartRepository;

    public function __construct(AddItemToCartHandler $handler, Security $security, CartRepositoryInterface $cartRepository)
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

            $data = json_decode($request->getContent(), true);
            if (!isset($data['product_id'])) {
                return new JsonResponse(['error' => 'product_id is required'], Response::HTTP_BAD_REQUEST);
            }

            $productId = ProductId::fromString($data['product_id']);
            $quantity = (int) ($data['quantity'] ?? 1);
            if ($quantity <= 0) {
                return new JsonResponse(['error' => 'quantity must be positive'], Response::HTTP_BAD_REQUEST);
            }

            $command = new AddItemToCartCommand($userId, $productId, $quantity);
            ($this->handler)($command);

            return new JsonResponse(['status' => 'ok'], Response::HTTP_CREATED);
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
