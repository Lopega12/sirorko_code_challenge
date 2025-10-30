<?php

namespace App\Product\Application\Http\Controller;

use App\Product\Application\Service\ProductService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class ListProductsController
{
    public function __construct(private ProductService $service)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $limit = (int) $request->query->get('limit', 50);
        $offset = (int) $request->query->get('offset', 0);
        $products = $this->service->list($limit, $offset);
        $data = array_map(fn ($p) => $p->toArray(), $products);

        return new JsonResponse($data);
    }
}
