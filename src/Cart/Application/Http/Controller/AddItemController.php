<?php

namespace App\Cart\Application\Http\Controller;

use App\Cart\Application\Command\AddItemToCartCommand;
use App\Cart\Application\Handler\AddItemToCartHandler;
use App\Cart\Domain\Port\CartRepositoryInterface;
use App\Cart\Domain\ProductId;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[AsController]
final class AddItemController
{
    use ResolveCartTrait;

    private AddItemToCartHandler $handler;
    private TokenStorageInterface $tokenStorage;
    private CartRepositoryInterface $cartRepository;

    public function __construct(AddItemToCartHandler $handler, TokenStorageInterface $tokenStorage, CartRepositoryInterface $cartRepository)
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

            return new JsonResponse(['error' => 'internal_error', 'message' => $msg],
                Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
