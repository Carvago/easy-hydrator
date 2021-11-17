<?php

declare(strict_types=1);

namespace Symplify\EasyHydrator;

use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionUnionType;
use Symplify\EasyHydrator\Exception\MissingConstructorException;

final class ClassConstructorValuesResolver
{
    public function __construct(
        private TypeCastersCollector $typeCastersCollector,
        private ParameterValueResolver $parameterValueResolver
    ) {
    }

    /**
     * @return array<int, mixed>
     */
    public function resolve(string $class, array $data): array
    {
        $arguments = [];

        $constructorReflectionMethod = $this->getConstructorMethodReflection($class);
        $reflectionParameters = $constructorReflectionMethod->getParameters();

        foreach ($reflectionParameters as $reflectionParameter) {
            $value = $this->parameterValueResolver->getValue($reflectionParameter, $data);

            // Union support
            $supportedTypeNames = match (true) {
                $reflectionParameter->getType() instanceof ReflectionUnionType => array_map(
                    callback: fn (ReflectionNamedType $type) => $type->getName(),
                    array: $reflectionParameter->getType()->getTypes(),
                ),
                $reflectionParameter->getType() instanceof ReflectionNamedType => $reflectionParameter->getType()->allowsNull() ?
                    [$reflectionParameter->getType()->getName(), 'null'] :
                    [$reflectionParameter->getType()->getName()],
                default => [],
            };
            $typeOrClass = is_object($value) ? get_class($value) : strtolower(gettype($value)); // gettype returns 'string', 'object', but 'NULL'
            $typeOrClass = match($typeOrClass) {
                'boolean' => 'bool',
                'integer' => 'int',
                'double' => 'float',
                'NULL' => 'null',
                default => $typeOrClass,
            };

            // Passing value if its already of one of declared types (excluding array)
            if ('array' !== $typeOrClass && in_array($typeOrClass, $supportedTypeNames)) {
                $arguments[] = $value;
            } else {
                $arguments[] = $this->typeCastersCollector->retype($value, $reflectionParameter, $this);
            }
        }

        return $arguments;
    }

    private function getConstructorMethodReflection(string $class): ReflectionMethod
    {
        $reflectionClass = new ReflectionClass($class);

        $constructorReflectionMethod = $reflectionClass->getConstructor();
        if (! $constructorReflectionMethod instanceof ReflectionMethod) {
            throw new MissingConstructorException(sprintf('Hydrated class "%s" is missing constructor.', $class));
        }

        return $constructorReflectionMethod;
    }
}
