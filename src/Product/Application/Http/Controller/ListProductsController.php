<?php

namespace App\Product\Application\Http\Controller;

use App\Product\Application\Service\ProductService;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final class ListProductsController
{
    public function __construct(private ProductService $service)
    {
    }

    #[OA\Get(
        path: '/api/products/',
        summary: 'Listar productos',
        description: 'Obtiene una lista paginada de productos del catálogo',
        tags: ['Products'],
        parameters: [
            new OA\Parameter(
                name: 'limit',
                in: 'query',
                description: 'Número máximo de productos a retornar',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 50, example: 20)
            ),
            new OA\Parameter(
                name: 'offset',
                in: 'query',
                description: 'Número de productos a saltar (paginación)',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 0, example: 0)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de productos',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'string', example: 'prod-123'),
                            new OA\Property(property: 'sku', type: 'string', example: 'SKU-001'),
                            new OA\Property(property: 'name', type: 'string', example: 'Producto Ejemplo'),
                            new OA\Property(property: 'price', type: 'number', format: 'float', example: 99.99),
                            new OA\Property(property: 'currency', type: 'string', example: 'EUR'),
                            new OA\Property(property: 'stock', type: 'integer', example: 100),
                        ]
                    )
                )
            ),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $limit = (int) $request->query->get('limit', 50);
        $offset = (int) $request->query->get('offset', 0);
        $products = $this->service->list($limit, $offset);
        $data = array_map(fn ($p) => $p->toArray(), $products);

        return new JsonResponse($data);
    }
}
