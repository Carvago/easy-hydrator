<?php

declare(strict_types=1);

use EAG\EasyHydrator\ArrayToValueObjectHydrator;
use EAG\EasyHydrator\EasyHydratorBundle;
use EAG\EasyHydrator\TypeCaster\ArrayTypeCaster;
use EAG\EasyHydrator\TypeCaster\DateTimeTypeCaster;
use EAG\EasyHydrator\TypeCaster\ObjectTypeCaster;
use EAG\EasyHydrator\TypeCaster\ScalarTypeCaster;
use EAG\EasyHydrator\TypeCastersCollector;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
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
