<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\EasyHydrator\EasyHydratorBundle;
use Symplify\EasyHydrator\TypeCastersCollector;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->defaults()
        ->public()
        ->autowire()
        ->autoconfigure();

    $services->load('Symplify\EasyHydrator\\', __DIR__ . '/../src')
        ->exclude([__DIR__ . '/../src/EasyHydratorBundle.php']);

    $services->set(TypeCastersCollector::class)
        ->args([tagged_iterator(EasyHydratorBundle::TYPE_CASTER_TAG)]);
};
