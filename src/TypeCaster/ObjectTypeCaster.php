<?php

declare(strict_types=1);

namespace Symplify\EasyHydrator\TypeCaster;

use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use Symplify\EasyHydrator\Contract\TypeCasterInterface;
use Symplify\EasyHydrator\ParameterTypeRecognizer;
use Symplify\EasyHydrator\ParameterValueResolver;
use Symplify\EasyHydrator\TypeCastersCollector;

final class ObjectTypeCaster implements TypeCasterInterface
{
    public function __construct(
        private ParameterTypeRecognizer $parameterTypeRecognizer,
        private ParameterValueResolver $parameterValueResolver,
    ) {
    }

    public function isSupported(ReflectionParameter $reflectionParameter): bool
    {
        $className = $this->getClassName($reflectionParameter);

        return null !== $className && class_exists($className) && null !== (new ReflectionClass($className))->getConstructor();
    }

    public function retype(
        mixed $value,
        ReflectionParameter $reflectionParameter,
        TypeCastersCollector $typeCastersCollector,
    ): mixed {
        $className = $this->getClassName($reflectionParameter);

        if ($className === null) {
            return $value;
        }

        if ($value === null && $reflectionParameter->allowsNull()) {
            return null;
        }

        if (! $this->parameterTypeRecognizer->isArray($reflectionParameter)) {
            return $this->createObject($className, $value, $typeCastersCollector);
        }

        return array_map(
            fn ($objectData) => $this->createObject($className, $objectData, $typeCastersCollector),
            $value
        );
    }

    public function getPriority(): int
    {
        return 5;
    }

    private function createObject(
        string $className,
        mixed $data,
        TypeCastersCollector $typeCastersCollector,
    ): mixed {
        if (is_a($data, $className) || !is_array($data)) {
            return $data;
        }

        $arguments = [];

        $reflectionParameters = (new ReflectionClass($className))->getConstructor()?->getParameters() ?? [];

        foreach ($reflectionParameters as $reflectionParameter) {
            $value = $this->parameterValueResolver->getValue($reflectionParameter, $data);

            $parameterTypeNames = $this->getParameterTypeNames($reflectionParameter);
            $valueTypeName = $this->getValueTypeName($value);

            // Passing value if its already of one of declared types (excluding array)
            if ('array' !== $valueTypeName && in_array($valueTypeName, $parameterTypeNames)) {
                $arguments[] = $value;
                continue;
            }

            $arguments[] = $typeCastersCollector->retype($value, $reflectionParameter, $typeCastersCollector);
        }

        return new $className(...$arguments);
    }

    private function getClassName(ReflectionParameter $reflectionParameter): ?string
    {
        if ($this->parameterTypeRecognizer->isArray($reflectionParameter)) {
            return $this->parameterTypeRecognizer->getTypeFromDocBlock($reflectionParameter);
        }

        return $this->parameterTypeRecognizer->getType($reflectionParameter);
    }

    private function getParameterTypeNames(ReflectionParameter $reflectionParameter): array
    {
        $parameterTypeNames = match (true) {
            $reflectionParameter->getType() instanceof ReflectionUnionType => array_map(
                callback: fn (ReflectionNamedType $type) => $type->getName(),
                array: $reflectionParameter->getType()->getTypes(),
            ),
            $reflectionParameter->getType() instanceof ReflectionNamedType => $reflectionParameter->getType()->allowsNull() ?
                [$reflectionParameter->getType()->getName(), 'null'] :
                [$reflectionParameter->getType()->getName()],
            default => [],
        };

        // Integers also could be passed as floats
        if (in_array('float', $parameterTypeNames) && !in_array('int', $parameterTypeNames)) {
            $parameterTypeNames[] = 'int';
        }

        return $parameterTypeNames;
    }

    private function getValueTypeName(mixed $value): string
    {
        $typeOrClass = is_object($value) ? get_class($value) : strtolower(gettype($value)); // gettype returns 'string', 'object', but 'NULL'
        return match($typeOrClass) {
            'boolean' => 'bool',
            'integer' => 'int',
            'double' => 'float',
            'NULL' => 'null',
            default => $typeOrClass,
        };
    }
}
