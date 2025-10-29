<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Health\Application\Http\Controllers\HealthCheckerController;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    // Registrar controladores
    $services->set(HealthCheckerController::class)
        ->tag('controller.service_arguments');
};
