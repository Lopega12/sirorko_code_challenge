<?php

use App\Health\Application\Http\Controllers\HealthCheckerController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes): void {
    $routes->add('health_checker', '/health')
        ->methods(['GET'])
        ->controller(HealthCheckerController::class);
};
