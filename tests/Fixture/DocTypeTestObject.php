<?php

declare(strict_types=1);

namespace Symplify\EasyHydrator\Tests\Fixture;

class DocTypeTestObject
{
    /**
     * @param string[] $props1
     * @param string[]|null $props2
     * @param array<string> $props3
     * @param array<string>|null $props4
     * @param array<int, string> $props5
     * @param array<int, string>|null $props6
     * @param Person[] $props11
     * @param Person[]|null $props12
     * @param array<Person> $props13
     * @param array<Person>|null $props14
     * @param array<int, Person> $props15
     * @param null|array<int, Person> $props16
     */
    public function __construct(
        public array $props1,
        public ?array $props2,
        public array $props3,
        public ?array $props4,
        public array $props5,
        public ?array $props6,
        public array $props11,
        public ?array $props12,
        public array $props13,
        public ?array $props14,
        public array $props15,
        public ?array $props16,
        public null|string|Person $props20,
        public null|Person|string $props21,
    ) {
    }
}
