<?php

namespace App\Product\Application\Http\Controller;

use App\Product\Application\Service\ProductService;
use App\Product\Domain\Repository\ProductRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class DeleteProductController
{
    public function __construct(private ProductService $service, private ProductRepositoryInterface $repo)
    {
    }

    #[IsGranted('ROLE_ADMIN')]
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
