<?php

declare(strict_types=1);

namespace EAG\EasyHydrator\TypeCaster;

use EAG\EasyHydrator\Contract\TypeCasterInterface;
use EAG\EasyHydrator\TypeDefinition;
use LogicException;
use RuntimeException;
use Stringable;

final class ScalarTypeCaster implements TypeCasterInterface
{
    public function isSupported(TypeDefinition $typeDefinition): bool
    {
        return $typeDefinition->supportsAnyOf(TypeDefinition::STRING, TypeDefinition::INT, TypeDefinition::FLOAT, TypeDefinition::BOOL);
    }

    public function retype(
        mixed $value,
        TypeDefinition $typeDefinition,
        TypeCasterInterface $rootTypeCaster,
    ): mixed {
        if ($typeDefinition->supportsValue($value)) {
            return $value;
        }

        if (TypeDefinition::STRING === $typeDefinition->getFirstAvailableType()) {
            // Since PHP 8 objects with __toString automatically has Stringable interface
            $isStringable = is_object($value) && $value instanceof Stringable;
            if (!is_scalar($value) && !$isStringable) {
                throw new RuntimeException('Expected scalar or Stringable for string conversion, given: ' . gettype($value));
            }

            $value = is_bool($value) ? (int) $value : $value;

            return (string) $value;
        }
        if (TypeDefinition::INT === $typeDefinition->getFirstAvailableType()) {
            if (!is_scalar($value)) {
                throw new RuntimeException('Expected scalar for int conversion, given: ' . gettype($value));
            }
            if (is_string($value) && 1 !== preg_match('/^\d+$/', $value)) {
                throw new RuntimeException('Expected string only with numbers for int conversion, given value: ' . $value);
            }
            if (is_float($value) && floor($value) !== $value) {
                throw new RuntimeException('Expected float without fractional part for int conversion, given value: ' . $value);
            }

            return (int) $value;
        }
        if (TypeDefinition::FLOAT === $typeDefinition->getFirstAvailableType()) {
            if (is_int($value)) {
                return $value;
            }
            if (!is_scalar($value)) {
                throw new RuntimeException('Expected scalar for float conversion, given: ' . gettype($value));
            }
            if (is_string($value) && !is_numeric($value)) {
                throw new RuntimeException('Expected numeric string for float conversion, given value: ' . $value);
            }

            return (float) $value;
        }
        if (TypeDefinition::BOOL === $typeDefinition->getFirstAvailableType()) {
            if (!is_scalar($value)) {
                throw new RuntimeException('Expected scalar for bool conversion, given: ' . gettype($value));
            }
            if (is_string($value) && !is_numeric($value)) {
                throw new RuntimeException('Expected numeric string for bool conversion, given value: ' . $value);
            }

            $value = is_string($value) ? (float) $value : $value;

            return (bool) $value;
        }

        throw new LogicException('TypeDefinition is supported: ' . $typeDefinition);
    }

    public function getPriority(): int
    {
        return 1;
    }
}
