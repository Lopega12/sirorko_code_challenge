<?php

namespace App\Product\Application\Http\Controller;

use App\Product\Application\Service\ProductService;
use App\Product\Domain\Repository\ProductRepositoryInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
final class DeleteProductController
{
    public function __construct(private ProductService $service, private ProductRepositoryInterface $repo)
    {
    }

    #[IsGranted('ROLE_ADMIN')]
    #[OA\Delete(
        path: '/api/products/{id}',
        summary: 'Eliminar un producto',
        description: 'Elimina un producto del catálogo (solo administradores)',
        security: [['bearerAuth' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID del producto a eliminar',
                schema: new OA\Schema(type: 'string', example: 'prod-123')
            ),
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Producto eliminado exitosamente'
            ),
            new OA\Response(response: 404, description: 'Producto no encontrado'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Acceso denegado (requiere ROLE_ADMIN)'),
        ]
    )]
    public function __invoke(string $id): JsonResponse
    {
        $product = $this->repo->find($id);
        if (!$product) {
            return new JsonResponse(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $this->service->delete($product);

        return new JsonResponse(null, Response::HTTP_OK);
    }
}
