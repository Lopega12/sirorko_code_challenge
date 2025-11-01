<?php

namespace App\Product\Application\Http\Controller;

use App\Product\Application\Service\ProductService;
use OpenApi\Attributes as OA;
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
    #[OA\Post(
        path: '/api/products/',
        summary: 'Crear un nuevo producto',
        description: 'Crea un nuevo producto en el catálogo (solo administradores)',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['sku', 'name', 'price', 'currency'],
                properties: [
                    new OA\Property(property: 'sku', type: 'string', example: 'SKU-001'),
                    new OA\Property(property: 'name', type: 'string', example: 'Producto Ejemplo'),
                    new OA\Property(property: 'price', type: 'number', format: 'float', example: 99.99),
                    new OA\Property(property: 'currency', type: 'string', example: 'EUR'),
                    new OA\Property(property: 'stock', type: 'integer', example: 100),
                    new OA\Property(property: 'description', type: 'string', example: 'Descripción del producto', nullable: true),
                ]
            )
        ),
        tags: ['Products'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Producto creado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'string', example: 'prod-123'),
                        new OA\Property(property: 'sku', type: 'string', example: 'SKU-001'),
                        new OA\Property(property: 'name', type: 'string', example: 'Producto Ejemplo'),
                        new OA\Property(property: 'price', type: 'number', format: 'float', example: 99.99),
                        new OA\Property(property: 'currency', type: 'string', example: 'EUR'),
                        new OA\Property(property: 'stock', type: 'integer', example: 100),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Datos inválidos'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Acceso denegado (requiere ROLE_ADMIN)'),
        ]
    )]
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
