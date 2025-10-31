<?php

use App\Cart\Domain\CartRepositoryInterface;
use App\Cart\Infrastructure\DoctrineCartRepository;
use App\Cart\Infrastructure\InMemoryCartRepository;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure()
        ->private();

    $services->set(DoctrineCartRepository::class)
        ->autowire()
        ->tag('doctrine.repository_service');

    $services->alias(CartRepositoryInterface::class, DoctrineCartRepository::class);

    // allow explicit use of InMemoryCartRepository in tests
    $services->set(InMemoryCartRepository::class)->public();
};
