<?php

declare(strict_types=1);

namespace EAG\EasyHydrator\Tests\Fixture;

final class TestArray
{
    /**
     * @param array<TestA> $values
     * @param array<string,TestA> $indexedValues
     * @param array<array<array<TestA>>> $nestedValues
     */
    public function __construct(
        public array $values,
        public array $indexedValues,
        public array $nestedValues,
    ) {
    }
}
