<?php

declare(strict_types=1);

namespace EAG\EasyHydrator\TypeCaster;

use EAG\EasyHydrator\Contract\TypeCasterInterface;
use EAG\EasyHydrator\TypeDefinition;
use RuntimeException;

final class ArrayTypeCaster implements TypeCasterInterface
{
    public function isSupported(TypeDefinition $typeDefinition): bool
    {
        return $typeDefinition->supports(TypeDefinition::ARRAY);
    }

    /**
     * @return array<mixed>|null
     */
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
