<?php

declare(strict_types=1);

namespace EAG\EasyHydrator\TypeCaster;

use EAG\EasyHydrator\Contract\TypeCasterInterface;
use EAG\EasyHydrator\TypeDefinition;
use UnexpectedValueException;

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
    ): mixed {
        if (!is_array($value) && $typeDefinition->supportsValue($value)) {
            return $value;
        }
        if (!is_array($value)) {
            throw new UnexpectedValueException('Expected array, given: ' . gettype($value));
        }
        if (null === $typeDefinition->getInnerTypeDefinition()) {
            throw new UnexpectedValueException('Array have no item type');
        }

        return array_map(fn (mixed $item) => $rootTypeCaster->retype($item, $typeDefinition->getInnerTypeDefinition(), $rootTypeCaster), $value);
    }

    public function getPriority(): int
    {
        return 5; // before ObjectTypeCaster
    }
}
