<?php

declare(strict_types=1);

namespace Symplify\EasyHydrator;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symplify\EasyHydrator\Contract\TypeCasterInterface;
use Symplify\EasyHydrator\DependencyInjection\Extension\EasyHydratorExtension;

final class EasyHydratorBundle extends Bundle
{
    const TYPE_CASTER_TAG = 'easy_hydrator.type_caster';
    
    protected function createContainerExtension(): EasyHydratorExtension
    {
        return new EasyHydratorExtension();
    }

    public function build(ContainerBuilder $container)
    {
        $container->registerForAutoconfiguration(TypeCasterInterface::class)
            ->addTag(self::TYPE_CASTER_TAG);
    }
}
