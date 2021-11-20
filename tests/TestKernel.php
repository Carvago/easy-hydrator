<?php

declare(strict_types=1);

namespace Symplify\EasyHydrator\Tests;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symplify\EasyHydrator\EasyHydratorBundle;

class TestKernel extends Kernel
{
    use MicroKernelTrait;

    /**
     * @inheritdoc
     */
    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new EasyHydratorBundle();
    }

    protected function configureContainer(ContainerBuilder $container): void
    {
        $container->loadFromExtension('framework', [
            'test' => true,
        ]);
    }

    public function getCacheDir(): string
    {
        return __DIR__ . '/cache';
    }
}
