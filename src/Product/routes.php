<?php

use App\Product\Application\Http\Controller\CreateProductController;
use App\Product\Application\Http\Controller\DeleteProductController;
use App\Product\Application\Http\Controller\ListProductsController;
use App\Product\Application\Http\Controller\ShowProductController;
use App\Product\Application\Http\Controller\UpdateProductController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes): void {
    $routes->add('api_products_index', '/')
        ->controller(ListProductsController::class)
        ->methods(['GET']);

    $routes->add('api_products_show', '/{id}')
        ->controller(ShowProductController::class)
        ->methods(['GET']);

    $routes->add('api_products_create', '/')
        ->controller(CreateProductController::class)
        ->methods(['POST']);

    $routes->add('api_products_update', '/{id}')
        ->controller(UpdateProductController::class)
        ->methods(['PUT', 'PATCH']);

    $routes->add('api_products_delete', '/{id}')
        ->controller(DeleteProductController::class)
        ->methods(['DELETE']);
};
