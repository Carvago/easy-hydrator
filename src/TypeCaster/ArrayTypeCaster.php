<?php

declare(strict_types=1);

namespace Symplify\EasyHydrator\TypeCaster;

use RuntimeException;
use Symplify\EasyHydrator\Contract\TypeCasterInterface;
use Symplify\EasyHydrator\TypeDefinition;

final class ArrayTypeCaster implements TypeCasterInterface
{
    public function isSupported(TypeDefinition $typeDefinition): bool
    {
        return $typeDefinition->supports(TypeDefinition::ARRAY);
    }

    public function retype(
        mixed $value,
        TypeDefinition $typeDefinition,
        TypeCasterInterface $rootTypeCaster,
    ): ?array {
        if (null === $value && $typeDefinition->supportsNull()) {
            return null;
        }
        if (!is_array($value)) {
            throw new RuntimeException('Expected array, given: ' . gettype($value));
        }
        if (null === $typeDefinition->getInnerTypeDefinition()) {
            throw new RuntimeException('Array have no item type');
        }

        return array_map(fn (mixed $item) => $rootTypeCaster->retype($item, $typeDefinition->getInnerTypeDefinition(), $rootTypeCaster), $value);
    }

    public function getPriority(): int
    {
        return 0;
    }
}
