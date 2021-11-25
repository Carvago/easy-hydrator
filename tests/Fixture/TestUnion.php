<?php

declare(strict_types=1);

namespace EAG\EasyHydrator\Tests\Fixture;

final class TestUnion
{
    public function __construct(public TestA | TestB $value)
    {
    }
}
