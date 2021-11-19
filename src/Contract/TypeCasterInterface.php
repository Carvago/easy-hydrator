<?php

declare(strict_types=1);

namespace Symplify\EasyHydrator\Contract;

use ReflectionParameter;
use Symplify\EasyHydrator\TypeCastersCollector;

interface TypeCasterInterface
{
    public function getPriority(): int;

    public function isSupported(ReflectionParameter $reflectionParameter): bool;

    public function retype(
        mixed $value,
        ReflectionParameter $reflectionParameter,
        TypeCastersCollector $typeCastersCollector,
    ): mixed;
}
