<?php

declare(strict_types=1);

namespace EAG\EasyHydrator\Tests;

use EAG\EasyHydrator\ArrayToValueObjectHydrator;
use EAG\EasyHydrator\EasyHydratorBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

class TestKernel extends Kernel implements CompilerPassInterface
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

    public function process(ContainerBuilder $container): void
    {
        $container->getDefinition(ArrayToValueObjectHydrator::class)
            ->setPublic(true);
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
