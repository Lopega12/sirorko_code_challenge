<?php

use App\Product\Application\Service\ProductService;
use App\Product\Domain\Repository\ProductRepositoryInterface;
use App\Product\Infrastructure\Repository\DoctrineProductRepository;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure()
        ->private();

    $services->set(DoctrineProductRepository::class)
        ->autowire()
        ->tag('doctrine.repository_service');

    $services->alias(ProductRepositoryInterface::class, DoctrineProductRepository::class);

    $services->set(ProductService::class)->public();
};
