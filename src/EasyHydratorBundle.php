<?php

declare(strict_types=1);

namespace EAG\EasyHydrator;

use EAG\EasyHydrator\Contract\TypeCasterInterface;
use EAG\EasyHydrator\DependencyInjection\Extension\EasyHydratorExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

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
