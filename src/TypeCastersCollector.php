<?php

declare(strict_types=1);

namespace Symplify\EasyHydrator;

use RuntimeException;
use Symplify\EasyHydrator\Contract\TypeCasterInterface;

class TypeCastersCollector implements TypeCasterInterface
{
    /**
     * @var array<TypeCasterInterface>
     */
    private array $typeCasters;

    /**
     * @param iterable<TypeCasterInterface> $typeCasters
     */
    public function __construct(iterable $typeCasters)
    {
        $this->typeCasters = $this->sortCastersByPriority([...$typeCasters]);
    }

    public function isSupported(TypeDefinition $typeDefinition): bool
    {
        return true;
    }

    public function retype(
        mixed $value,
        TypeDefinition $typeDefinition,
        TypeCasterInterface $rootTypeCaster,
    ): mixed {
        foreach ($this->typeCasters as $typeCaster) {
            if ($typeCaster->isSupported($typeDefinition)) {
                return $typeCaster->retype($value, $typeDefinition, $rootTypeCaster);
            }
        }

        throw new RuntimeException('No TypeCaster found to handle type: ' . $typeDefinition);
    }

    public function getPriority(): int
    {
        return 0;
    }

    /**
     * Bigger number means more prioritized execution
     *
     * @param array<TypeCasterInterface> $typeCasters
     * @return array<TypeCasterInterface>
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
