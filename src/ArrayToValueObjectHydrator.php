<?php

declare(strict_types=1);

namespace EAG\EasyHydrator;

final class ArrayToValueObjectHydrator
{
    public function __construct(private TypeCastersCollector $typeCastersCollector)
    {
    }

    /**
     * @template T of object
     * @param array<mixed> $data
     * @param class-string<T> $class
     * @return T
     */
    public function hydrateArray(array $data, string $class): object
    {
        return $this->typeCastersCollector->retype(
            value: $data,
            typeDefinition: new TypeDefinition([$class]),
            rootTypeCaster: $this->typeCastersCollector,
        );
    }

    /**
     * @template T of object
     * @param array<array<mixed>> $data
     * @param class-string<T> $class
     * @return array<T>
     */
    public function hydrateArrays(array $data, string $class): array
    {
        return array_map(fn (array $item) => $this->hydrateArray($item, $class), $data);
    }
}
