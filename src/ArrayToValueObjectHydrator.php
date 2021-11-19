<?php

declare(strict_types=1);

namespace Symplify\EasyHydrator;

use ReflectionClass;
use ReflectionParameter;
use RuntimeException;
use Symplify\EasyHydrator\Exception\MissingConstructorException;

/**
 * @see \Symplify\EasyHydrator\Tests\ArrayToValueObjectHydratorTest
 */
final class ArrayToValueObjectHydrator
{
    public function __construct(
        private TypeCastersCollector $typeCastersCollector,
    ) {
    }

    /**
     * @template T of object
     * @param mixed[] $data
     * @param class-string<T> $class
     * @return T
     */
    public function hydrateArray(array $data, string $class): object
    {
        // Additional check to secure eval call
        if (!class_exists($class)) {
            throw new RuntimeException('Class not exist: ' . $class);
        }

        if (null === (new ReflectionClass($class))->getConstructor()) {
            throw new MissingConstructorException(sprintf('Hydrated class "%s" is missing constructor.', $class));
        }

        // A workaround to create an instance of ReflectionParameter to init retype process
        $callback = eval("return fn ($class \$value) => null;");
        $reflectionParameter = new ReflectionParameter($callback, 'value');

        return $this->typeCastersCollector->retype($data, $reflectionParameter, $this->typeCastersCollector);
    }

    /**
     * @template T of object
     * @param mixed[][] $datas
     * @param class-string<T> $class
     * @return array<T>
     */
    public function hydrateArrays(array $datas, string $class): array
    {
        $valueObjects = [];
        foreach ($datas as $data) {
            $valueObjects[] = $this->hydrateArray($data, $class);
        }

        return $valueObjects;
    }
}
