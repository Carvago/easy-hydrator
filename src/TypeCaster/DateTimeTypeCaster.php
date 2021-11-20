<?php

declare(strict_types=1);

namespace Symplify\EasyHydrator\TypeCaster;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use ReflectionParameter;
use RuntimeException;
use Symplify\EasyHydrator\Contract\TypeCasterInterface;
use Symplify\EasyHydrator\ParameterTypeRecognizer;
use Symplify\EasyHydrator\TypeCastersCollector;

final class DateTimeTypeCaster implements TypeCasterInterface
{
    public function __construct(
        private ParameterTypeRecognizer $parameterTypeRecognizer
    ) {
    }

    public function isSupported(ReflectionParameter $reflectionParameter): bool
    {
        $type = $this->parameterTypeRecognizer->getType($reflectionParameter);

        return null !== $type && is_a($type, DateTimeInterface::class, true);
    }

    public function retype(
        mixed $value,
        ReflectionParameter $reflectionParameter,
        TypeCastersCollector $typeCastersCollector,
    ): ?DateTimeInterface {
        if ($value === null && $reflectionParameter->allowsNull()) {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value;
        }

        $datetime = DateTime::createFromFormat(DateTime::ATOM, $value);
        $datetime = false !== $datetime ? $datetime : DateTime::createFromFormat(DateTime::RFC3339_EXTENDED, $value);
        $datetime = false !== $datetime ? $datetime : DateTime::createFromFormat('Y-m-d', $value);
        $datetime = false !== $datetime ? $datetime : DateTime::createFromFormat('Y-m-d H:i:s', $value);
        if (false === $datetime) {
            throw new RuntimeException('Unsupported datetime format: ' . $value);
        }

        $class = $this->parameterTypeRecognizer->getType($reflectionParameter);

        if ($class === DateTimeImmutable::class) {
            return DateTimeImmutable::createFromMutable($datetime);
        }

        return $datetime;
    }

    public function getPriority(): int
    {
        return 10;
    }
}
