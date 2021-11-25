<?php

declare(strict_types=1);

namespace EAG\EasyHydrator\TypeCaster;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use EAG\EasyHydrator\Contract\TypeCasterInterface;
use EAG\EasyHydrator\TypeDefinition;
use Nette\Utils\DateTime as NetteDateTime;
use RuntimeException;

final class DateTimeTypeCaster implements TypeCasterInterface
{
    public function isSupported(TypeDefinition $typeDefinition): bool
    {
        return $typeDefinition->supportsAnyOf(DateTimeInterface::class, DateTime::class, DateTimeImmutable::class);
    }

    public function retype(
        mixed $value,
        TypeDefinition $typeDefinition,
        TypeCasterInterface $rootTypeCaster,
    ): ?DateTimeInterface {
        if ($typeDefinition->supportsValue($value)) {
            return $value;
        }
        if (!is_string($value)) {
            throw new RuntimeException('Expected string, given: ' . gettype($value));
        }

        $dateTime = NetteDateTime::from($value);

        if ($typeDefinition->getFirstAvailableType() === DateTimeImmutable::class) {
            return DateTimeImmutable::createFromMutable($dateTime);
        }

        return $dateTime;
    }

    public function getPriority(): int
    {
        return 10;
    }
}
