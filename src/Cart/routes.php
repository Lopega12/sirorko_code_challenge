<?php

use App\Cart\Application\Http\Controller\AddItemController;
use App\Cart\Application\Http\Controller\CheckoutCartController;
use App\Cart\Application\Http\Controller\GetCartController;
use App\Cart\Application\Http\Controller\RemoveItemController;
use App\Cart\Application\Http\Controller\UpdateItemController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes): void {
    // Active routes that do NOT require cartId; controller will resolve cart by token
    $routes->add('api_cart_get', '/')
        ->controller(GetCartController::class)
        ->methods(['GET']);

    $routes->add('api_cart_add_item', '/items')
        ->controller(AddItemController::class)
        ->methods(['POST']);

    $routes->add('api_cart_update_item', '/items/{productId}')
        ->controller(UpdateItemController::class)
        ->methods(['PUT', 'PATCH']);

    $routes->add('api_cart_remove_item', '/items/{productId}')
        ->controller(RemoveItemController::class)
        ->methods(['DELETE']);

    $routes->add('api_cart_checkout', '/checkout')
        ->controller(CheckoutCartController::class)
        ->methods(['POST']);
};
