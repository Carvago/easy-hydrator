<?php

declare(strict_types=1);

namespace EAG\EasyHydrator\Tests\Fixture;

final class TestDefaults
{
    public function __construct(
        public string $value1 = 'test',
        public int $value2 = 100,
        public float $value3 = 111.111,
        public bool $value4 = true,
        public ?string $value5 = null,
        public string | null $value6 = null,
    ) {
    }
}
