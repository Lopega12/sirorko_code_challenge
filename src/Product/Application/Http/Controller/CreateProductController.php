<?php

namespace App\Product\Application\Http\Controller;

use App\Product\Application\Service\ProductService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
final class CreateProductController
{
    public function __construct(private ProductService $service)
    {
    }

    #[IsGranted('ROLE_ADMIN')]
    public function __invoke(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent() ?: '{}', true);

        if (!isset($payload['sku'], $payload['name'], $payload['price'], $payload['currency'])) {
            return new JsonResponse(['message' => 'Invalid payload'], Response::HTTP_BAD_REQUEST);
        }

        $product = $this->service->create(
            (string) $payload['sku'],
            (string) $payload['name'],
            (float) $payload['price'],
            (string) $payload['currency'],
            (int) ($payload['stock'] ?? 0),
            isset($payload['description']) ? (string) $payload['description'] : null
        );

        return new JsonResponse($product->toArray(), Response::HTTP_CREATED);
    }
}
