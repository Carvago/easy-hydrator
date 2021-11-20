<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\EasyHydrator\Contract\TypeCasterInterface;
use Symplify\EasyHydrator\TypeCastersCollector;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

const TYPE_CASTER_TAG = 'easy_hydrator.type_caster';

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->defaults()
        ->public()
        ->autowire()
        ->autoconfigure();

    $services->instanceof(TypeCasterInterface::class)
        ->tag(TYPE_CASTER_TAG);

    $services->load('Symplify\EasyHydrator\\', __DIR__ . '/../src')
        ->exclude([__DIR__ . '/../src/EasyHydratorBundle.php']);

    $services->set(TypeCastersCollector::class)
        ->args([tagged_iterator(TYPE_CASTER_TAG)]);
};
