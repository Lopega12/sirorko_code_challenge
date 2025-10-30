<?php

namespace App\Product\Application\Http\Controller;

use App\Product\Application\Service\ProductService;
use Symfony\Component\HttpFoundation\JsonResponse;

final class ShowProductController
{
    public function __construct(private ProductService $service)
    {
    }

    public function __invoke(string $id): JsonResponse
    {
        $product = $this->service->get($id);
        if (!$product) {
            return new JsonResponse(['message' => 'Not Found'], JsonResponse::HTTP_NOT_FOUND);
        }

        return new JsonResponse($product->toArray());
    }
}
