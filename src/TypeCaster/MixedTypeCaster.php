<?php

declare(strict_types=1);

namespace EAG\EasyHydrator\TypeCaster;

use EAG\EasyHydrator\Contract\TypeCasterInterface;
use EAG\EasyHydrator\TypeDefinition;

final class MixedTypeCaster implements TypeCasterInterface
{
    public function isSupported(TypeDefinition $typeDefinition): bool
    {
        return $typeDefinition->supports(TypeDefinition::MIXED);
    }

    public function retype(
        mixed $value,
        TypeDefinition $typeDefinition,
        TypeCasterInterface $rootTypeCaster,
    ): mixed {
        return $value;
    }

    public function getPriority(): int
    {
        // Lowest priority
        return -100;
    }
}
