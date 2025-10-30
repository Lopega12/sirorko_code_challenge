<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Auth\Application\Http\Controller\AuthController;
use App\Auth\Application\Http\Controller\LogoutController;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    // Registrar controladores
    $services->set(AuthController::class)
        ->public()
        ->tag('controller.service_arguments')
        ->args([
            '$loginAttemptsLimiter' => service('limiter.login_attempts'),
        ]);
    $services->set(LogoutController::class)
        ->public()
        ->tag('controller.service_arguments');
};
