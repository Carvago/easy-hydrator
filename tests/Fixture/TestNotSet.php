<?php

declare(strict_types=1);

namespace EAG\EasyHydrator\Tests\Fixture;

class TestNotSet implements TestInterface
{
    /**
     * @param array<string>|null|NotSet $arrayOfStrings
     * @param array<TestA>|null|NotSet $arrayOfObjects
     */
    public function __construct(
        public string | null | NotSet $string = new NotSet(),
        public TestA | null | NotSet $object = new NotSet(),
        public array | null | NotSet $arrayOfStrings = new NotSet(),
        public array | null | NotSet $arrayOfObjects = new NotSet(),
    ) {
    }
}
