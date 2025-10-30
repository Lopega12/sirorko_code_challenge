<?php

use App\Auth\Application\Http\Controller\AuthController;
use App\Auth\Application\Http\Controller\LogoutController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes): void {
    $routes->add('api_auth_login', '/login')
        ->controller(AuthController::class)
        ->methods(['POST']);

    $routes->add('api_auth_logout', '/logout')
        ->controller(LogoutController::class)
        ->methods(['POST']);
};
