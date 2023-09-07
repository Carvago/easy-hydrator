<?php

declare(strict_types=1);

namespace EAG\EasyHydrator\TypeCaster;

use EAG\EasyHydrator\Contract\TypeCasterInterface;
use EAG\EasyHydrator\Exception\MissingConstructorException;
use EAG\EasyHydrator\Exception\MissingDataException;
use EAG\EasyHydrator\TypeDefinition;
use EAG\EasyHydrator\TypeDefinitionBuilder;
use ReflectionClass;
use ReflectionParameter;
use UnexpectedValueException;
use function Symfony\Component\String\b;

final class ObjectTypeCaster implements TypeCasterInterface
{
    public function isSupported(TypeDefinition $typeDefinition): bool
    {
        return class_exists($typeDefinition->getFirstAvailableType());
    }

    public function retype(
        mixed $value,
        TypeDefinition $typeDefinition,
        TypeCasterInterface $rootTypeCaster,
    ): mixed {
        if ($typeDefinition->supportsValue($value)) {
            return $value;
        }
        if (!is_array($value)) {
            throw new UnexpectedValueException('Expected array, given: ' . gettype($value));
        }

        $className = $typeDefinition->getFirstAvailableType();
        if (!class_exists($className)) {
            throw new UnexpectedValueException('First declared type expected to be class, given: ' . $className);
        }

        if (null === (new ReflectionClass($className))->getConstructor()) {
            throw new MissingConstructorException(sprintf('Hydrated class "%s" is missing constructor.', $className));
        }

        $arguments = [];

        $reflectionParameters = (new ReflectionClass($className))->getConstructor()?->getParameters() ?? [];
        foreach ($reflectionParameters as $reflectionParameter) {
            $arguments[] = $rootTypeCaster->retype(
                value: self::findValue($reflectionParameter, $value),
                typeDefinition: TypeDefinitionBuilder::create($reflectionParameter)->build(),
                rootTypeCaster: $rootTypeCaster,
            );
        }

        return new $className(...$arguments);
    }

    public function getPriority(): int
    {
        return 3;
    }

    /**
     * @param array<mixed> $data
     * @return mixed
     * @throws MissingDataException
     */
    private static function findValue(ReflectionParameter $reflectionParameter, array $data): mixed
    {
        $parameterName = $reflectionParameter->name;
        $underscoreParameterName = b($reflectionParameter->name)->snake()->toString();

        if (array_key_exists($parameterName, $data)) {
            return $data[$parameterName];
        }

        if (array_key_exists($underscoreParameterName, $data)) {
            return $data[$underscoreParameterName];
        }

        if ($reflectionParameter->isDefaultValueAvailable()) {
            return $reflectionParameter->getDefaultValue();
        }

        $declaringReflectionClass = $reflectionParameter->getDeclaringClass();

        throw new MissingDataException(sprintf(
            'Missing data of "$%s" parameter for hydrated class "%s" __construct method.',
            $parameterName,
            $declaringReflectionClass !== null ? $declaringReflectionClass->getName() : ''
        ));
    }
}
