<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\EasyHydrator\ArrayToValueObjectHydrator;
use Symplify\EasyHydrator\EasyHydratorBundle;
use Symplify\EasyHydrator\TypeCaster\ArrayTypeCaster;
use Symplify\EasyHydrator\TypeCaster\DateTimeTypeCaster;
use Symplify\EasyHydrator\TypeCaster\ObjectTypeCaster;
use Symplify\EasyHydrator\TypeCaster\ScalarTypeCaster;
use Symplify\EasyHydrator\TypeCastersCollector;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->defaults()
        ->autoconfigure()
        ->autowire();

    $services->set(ArrayTypeCaster::class);
    $services->set(DateTimeTypeCaster::class);
    $services->set(ObjectTypeCaster::class);
    $services->set(ScalarTypeCaster::class);

    $services->set(TypeCastersCollector::class)
        ->autoconfigure(false)
        ->args([tagged_iterator(EasyHydratorBundle::TYPE_CASTER_TAG)]);

    $services->set(ArrayToValueObjectHydrator::class);
};
