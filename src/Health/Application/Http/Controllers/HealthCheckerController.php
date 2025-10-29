<?php

namespace App\Health\Application\Http\Controllers;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class HealthCheckerController
{
    public function __invoke(): JsonResponse
    {
        return new JsonResponse(['status' => 'ok'], Response::HTTP_OK);
    }
}
