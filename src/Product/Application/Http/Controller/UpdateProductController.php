<?php

namespace App\Product\Application\Http\Controller;

use App\Product\Application\Service\ProductService;
use App\Product\Domain\Repository\ProductRepositoryInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
final class UpdateProductController
{
    public function __construct(private ProductService $service, private ProductRepositoryInterface $repo)
    {
    }

    #[IsGranted('ROLE_ADMIN')]
    #[OA\Put(
        path: '/api/products/{id}',
        summary: 'Actualizar un producto',
        description: 'Actualiza la información de un producto existente (solo administradores)',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Producto Actualizado'),
                    new OA\Property(property: 'price', type: 'number', format: 'float', example: 129.99),
                    new OA\Property(property: 'stock', type: 'integer', example: 50),
                    new OA\Property(property: 'description', type: 'string', example: 'Nueva descripción'),
                ]
            )
        ),
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
                description: 'Producto actualizado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'string', example: 'prod-123'),
                        new OA\Property(property: 'sku', type: 'string', example: 'SKU-001'),
                        new OA\Property(property: 'name', type: 'string', example: 'Producto Actualizado'),
                        new OA\Property(property: 'price', type: 'number', format: 'float', example: 129.99),
                        new OA\Property(property: 'currency', type: 'string', example: 'EUR'),
                        new OA\Property(property: 'stock', type: 'integer', example: 50),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Producto no encontrado'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Acceso denegado (requiere ROLE_ADMIN)'),
        ]
    )]
    #[OA\Patch(
        path: '/api/products/{id}',
        summary: 'Actualizar parcialmente un producto',
        description: 'Actualiza parcialmente la información de un producto existente (solo administradores)',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Producto Actualizado'),
                    new OA\Property(property: 'price', type: 'number', format: 'float', example: 129.99),
                    new OA\Property(property: 'stock', type: 'integer', example: 50),
                    new OA\Property(property: 'description', type: 'string', example: 'Nueva descripción'),
                ]
            )
        ),
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
                description: 'Producto actualizado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'string', example: 'prod-123'),
                        new OA\Property(property: 'sku', type: 'string', example: 'SKU-001'),
                        new OA\Property(property: 'name', type: 'string', example: 'Producto Actualizado'),
                        new OA\Property(property: 'price', type: 'number', format: 'float', example: 129.99),
                        new OA\Property(property: 'currency', type: 'string', example: 'EUR'),
                        new OA\Property(property: 'stock', type: 'integer', example: 50),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Producto no encontrado'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Acceso denegado (requiere ROLE_ADMIN)'),
        ]
    )]
    public function __invoke(string $id, Request $request): JsonResponse
    {
        $product = $this->repo->find($id);
        if (!$product) {
            return new JsonResponse(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $payload = json_decode($request->getContent() ?: '{}', true);
        $product = $this->service->update($product, $payload);

        return new JsonResponse($product->toArray());
    }
}
