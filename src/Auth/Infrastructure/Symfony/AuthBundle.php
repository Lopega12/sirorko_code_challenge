<?php

namespace App\Auth\Infrastructure\Symfony;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class AuthBundle extends Bundle
{
    public function getPath(): string
    {
        return dirname(__DIR__, 2);
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $directory = $this->getPath().'/Infrastructure/DependencyInjection';
        $loader = new PhpFileLoader($container, new FileLocator($directory));
        $loader->load('services.php');
    }
}
