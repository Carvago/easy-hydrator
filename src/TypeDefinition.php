<?php

declare(strict_types=1);

namespace Symplify\EasyHydrator;

use InvalidArgumentException;
use Stringable;

final class TypeDefinition implements Stringable
{
    public const ARRAY = 'array';
    public const STRING = 'string';
    public const INT = 'int';
    public const FLOAT = 'float';
    public const BOOL = 'bool';
    public const NULL = 'null';

    /**
     * @param non-empty-array<string|class-string> $types
     */
    public function __construct(private array $types, private ?TypeDefinition $innerTypeDefinition = null)
    {
        if (empty($this->types)) {
            throw new InvalidArgumentException('List of types could not be empty');
        }
        if ([] === array_filter($this->types, fn (string $type) => self::NULL !== $type)) {
            throw new InvalidArgumentException('List of types should contain at least on type along with null');
        }
        if (in_array(self::ARRAY, $this->types) && !$this->innerTypeDefinition) {
            throw new InvalidArgumentException('Type supports array, but have no specified inner type definition');
        }
        if ($this->innerTypeDefinition && !in_array(self::ARRAY, $this->types)) {
            throw new InvalidArgumentException('Type has inner type definition, but does not support array');
        }
        foreach ($this->types as $type) {
            $isBuiltin = in_array($type, [self::ARRAY, self::STRING, self::INT, self::FLOAT, self::BOOL, self::NULL]);
            $isClass = class_exists($type) || interface_exists($type);
            if (!$isBuiltin && !$isClass) {
                throw new InvalidArgumentException('Provided value is not a type or class/interface: ' . $type);
            }
        }
    }

    /**
     * @return non-empty-array<string|class-string>
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    public function getInnerTypeDefinition(): ?TypeDefinition
    {
        return $this->innerTypeDefinition;
    }

    public function supportsValue(mixed $value): bool
    {
        // gettype returns 'string', 'object', but 'NULL'
        $typeOrClass = is_object($value) ? get_class($value) : strtolower(gettype($value));
        $valueType = match($typeOrClass) {
            'boolean' => 'bool',
            'integer' => 'int',
            'double' => 'float',
            'NULL' => 'null',
            default => $typeOrClass,
        };

        return $this->supports($valueType) || (is_int($value) && $this->supports(self::FLOAT));
    }

    public function getFirstAvailableType(): string
    {
        return array_values(array_filter($this->types, fn (string $type) => self::NULL !== $type))[0];
    }

    /**
     * @param string|class-string $type
     * @return bool
     */
    public function supports(string $type): bool
    {
        return $this->supportsAnyOf($type);
    }

    /**
     * @param array<string|class-string> $types
     * @return bool
     */
    public function supportsAnyOf(string ...$types): bool
    {
        foreach ($this->types as $availableType) {
            foreach ($types as $type) {
                if ($availableType === $type || is_a($type, $availableType, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function supportsNull(): bool
    {
        return in_array(self::NULL, $this->types);
    }

    public function __toString(): string
    {
        return implode('|', array_map(function(string $type): string {
            if ('array' === $type && $this->innerTypeDefinition) {
                return 'array<' . $this->innerTypeDefinition . '>';
            }
            return $type;
        }, $this->types));
    }
}
