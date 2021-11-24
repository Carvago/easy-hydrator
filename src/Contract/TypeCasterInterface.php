<?php

declare(strict_types=1);

namespace Symplify\EasyHydrator\Contract;

use Symplify\EasyHydrator\TypeDefinition;

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
