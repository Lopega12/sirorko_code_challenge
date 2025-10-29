<?php

namespace App\Health\Application\Http\Controllers;

use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class HealthCheckerController
{
    #[OA\Get(
        path: '/api/health',
        operationId: 'healthCheck',
        description: 'Endpoints to check the health status of the service',
        summary: 'Health check endpoint',
        tags: ['health'],
    )]
    #[OA\Response(
        response: 200,
        description: 'Service is healthy',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'ok'),
            ],
            type: 'object',
            example: ['status' => 'ok']
        )
    )]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse(['status' => 'ok'], Response::HTTP_OK);
    }
}
