<?php

declare(strict_types=1);

use App\Order\Application\Http\Controller\CancelOrderController;
use App\Order\Application\Http\Controller\GetOrderController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->add('get_order', '/api/orders/{orderId}')
        ->controller(GetOrderController::class)
        ->methods(['GET']);

    $routes->add('cancel_order', '/api/orders/{orderId}/cancel')
        ->controller(CancelOrderController::class)
        ->methods(['POST']);
};
