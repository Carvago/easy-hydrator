<?php

declare(strict_types=1);

namespace EAG\EasyHydrator\Contract;

use EAG\EasyHydrator\TypeDefinition;

interface TypeCasterInterface
{
    public function getPriority(): int;

    public function isSupported(TypeDefinition $typeDefinition): bool;

    public function retype(
        mixed $value,
        TypeDefinition $typeDefinition,
        TypeCasterInterface $rootTypeCaster,
    ): mixed;
}
