<?php

use App\Order\Domain\OrderRepositoryInterface;
use App\Order\Infrastructure\DoctrineOrderRepository;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure()
        ->private();

    $services->set(DoctrineOrderRepository::class)
        ->autowire()
        ->tag('doctrine.repository_service');

    $services->alias(OrderRepositoryInterface::class, DoctrineOrderRepository::class);
};
