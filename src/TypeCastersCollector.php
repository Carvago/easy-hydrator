<?php

declare(strict_types=1);

namespace Symplify\EasyHydrator;

use ReflectionParameter;
use Symplify\EasyHydrator\Contract\TypeCasterInterface;

final class TypeCastersCollector
{
    /**
     * @var TypeCasterInterface[]
     */
    private array $typeCasters;

    /**
     * @param iterable<TypeCasterInterface> $typeCasters
     */
    public function __construct(iterable $typeCasters)
    {
        $this->typeCasters = $this->sortCastersByPriority([...$typeCasters]);
    }

    public function retype(
        mixed $value,
        ReflectionParameter $reflectionParameter,
        TypeCastersCollector $typeCastersCollector,
    ): mixed {
        foreach ($this->typeCasters as $typeCaster) {
            if ($typeCaster->isSupported($reflectionParameter)) {
                return $typeCaster->retype($value, $reflectionParameter, $typeCastersCollector);
            }
        }

        return $value;
    }

    /**
     * Bigger number means more prioritized execution
     *
     * @param TypeCasterInterface[] $typeCasters
     * @return TypeCasterInterface[]
     */
    private function sortCastersByPriority(array $typeCasters): array
    {
        usort(
            $typeCasters,
            // Sorting $b <=> $a to get casters with bigger value reveal first
            static fn (TypeCasterInterface $a, TypeCasterInterface $b): int => $b->getPriority() <=> $a->getPriority()
        );

        return $typeCasters;
    }
}
