<?php

namespace App\Cart\Application\Http\Controller;

use App\Cart\Application\Command\AddItemToCartCommand;
use App\Cart\Application\Command\CheckoutCartCommand;
use App\Cart\Application\Command\RemoveItemFromCartCommand;
use App\Cart\Application\Command\UpdateItemQuantityCommand;
use App\Cart\Application\Handler\AddItemToCartHandler;
use App\Cart\Application\Handler\CheckoutCartHandler;
use App\Cart\Application\Handler\RemoveItemFromCartHandler;
use App\Cart\Application\Handler\UpdateItemQuantityHandler;
use App\Cart\Domain\CartId;
use App\Cart\Domain\CartRepositoryInterface;
use App\Cart\Domain\ProductId;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;

final class CartController
{
    private AddItemToCartHandler $addHandler;
    private UpdateItemQuantityHandler $updateHandler;
    private RemoveItemFromCartHandler $removeHandler;
    private CheckoutCartHandler $checkoutHandler;
    private CartRepositoryInterface $cartRepository;
    private Security $security;

    public function __construct(
        AddItemToCartHandler $addHandler,
        UpdateItemQuantityHandler $updateHandler,
        RemoveItemFromCartHandler $removeHandler,
        CheckoutCartHandler $checkoutHandler,
        CartRepositoryInterface $cartRepository,
        Security $security,
    ) {
        $this->addHandler = $addHandler;
        $this->updateHandler = $updateHandler;
        $this->removeHandler = $removeHandler;
        $this->checkoutHandler = $checkoutHandler;
        $this->cartRepository = $cartRepository;
        $this->security = $security;
    }

    private function getUserId(): ?string
    {
        $user = $this->security->getUser();
        if (!$user) {
            return null;
        }

        // User entity exposes getId()
        return method_exists($user, 'getId') ? $user->getId() : null;
    }

    public function add(string $cartId, Request $request): JsonResponse
    {
        try {
            $userId = $this->getUserId();
            if (!$userId) {
                return new JsonResponse(['error' => 'unauthenticated'], Response::HTTP_UNAUTHORIZED);
            }

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
            ($this->addHandler)($command);

            return new JsonResponse(['status' => 'ok'], Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'internal_error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(string $cartId, string $productId, Request $request): JsonResponse
    {
        try {
            $userId = $this->getUserId();
            if (!$userId) {
                return new JsonResponse(['error' => 'unauthenticated'], Response::HTTP_UNAUTHORIZED);
            }

            $data = json_decode($request->getContent(), true);
            $quantity = (int) ($data['quantity'] ?? 1);
            if ($quantity < 0) {
                return new JsonResponse(['error' => 'quantity must be >= 0'], Response::HTTP_BAD_REQUEST);
            }

            $command = new UpdateItemQuantityCommand($userId, ProductId::fromString($productId), $quantity);
            ($this->updateHandler)($command);

            return new JsonResponse(['status' => 'ok']);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'internal_error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function remove(string $cartId, string $productId): JsonResponse
    {
        try {
            $userId = $this->getUserId();
            if (!$userId) {
                return new JsonResponse(['error' => 'unauthenticated'], Response::HTTP_UNAUTHORIZED);
            }

            $command = new RemoveItemFromCartCommand($userId, ProductId::fromString($productId));
            ($this->removeHandler)($command);

            return new JsonResponse([], Response::HTTP_NO_CONTENT);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'internal_error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function get(string $cartId): JsonResponse
    {
        try {
            $cart = $this->cartRepository->get(CartId::fromString($cartId));
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

    public function checkout(string $cartId): JsonResponse
    {
        try {
            $userId = $this->getUserId();
            if (!$userId) {
                return new JsonResponse(['error' => 'unauthenticated'], Response::HTTP_UNAUTHORIZED);
            }

            $command = new CheckoutCartCommand($userId);
            $orderId = ($this->checkoutHandler)($command);

            return new JsonResponse(['order_id' => (string) $orderId]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'internal_error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
