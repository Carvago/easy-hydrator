<?php

declare(strict_types=1);

namespace EAG\EasyHydrator\DependencyInjection\Extension;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

final class EasyHydratorExtension extends Extension
{
    /**
     * @param array<string> $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $phpFileLoader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../../config'));
        $phpFileLoader->load('config.php');
    }
}
