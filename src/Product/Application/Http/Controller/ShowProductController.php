<?php

namespace App\Product\Application\Http\Controller;

use App\Product\Application\Service\ProductService;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final class ShowProductController
{
    public function __construct(private ProductService $service)
    {
    }

    #[OA\Get(
        path: '/api/products/{id}',
        summary: 'Obtener detalle de un producto',
        description: 'Obtiene la información detallada de un producto específico',
        tags: ['Products'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID del producto',
                schema: new OA\Schema(type: 'string', example: 'prod-123')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detalle del producto',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'string', example: 'prod-123'),
                        new OA\Property(property: 'sku', type: 'string', example: 'SKU-001'),
                        new OA\Property(property: 'name', type: 'string', example: 'Producto Ejemplo'),
                        new OA\Property(property: 'price', type: 'number', format: 'float', example: 99.99),
                        new OA\Property(property: 'currency', type: 'string', example: 'EUR'),
                        new OA\Property(property: 'stock', type: 'integer', example: 100),
                        new OA\Property(property: 'description', type: 'string', example: 'Descripción del producto'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Producto no encontrado'),
        ]
    )]
    public function __invoke(string $id): JsonResponse
    {
        $product = $this->service->get($id);
        if (!$product) {
            return new JsonResponse(['message' => 'Not Found'], JsonResponse::HTTP_NOT_FOUND);
        }

        return new JsonResponse($product->toArray());
    }
}
