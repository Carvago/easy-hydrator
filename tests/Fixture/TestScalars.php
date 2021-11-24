<?php

declare(strict_types=1);

namespace Symplify\EasyHydrator\Tests\Fixture;

final class TestScalars
{
    /**
     * @param array<string> $strings
     * @param array<int> $integers
     * @param array<float> $floats
     * @param array<bool> $booleans
     */
    public function __construct(
        public array $strings = [],
        public array $integers = [],
        public array $floats = [],
        public array $booleans = [],
    ) {
    }
}
