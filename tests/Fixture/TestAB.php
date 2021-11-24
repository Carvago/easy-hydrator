<?php

declare(strict_types=1);

namespace Symplify\EasyHydrator\Tests\Fixture;

final class TestAB implements TestInterface
{
    public function __construct(public TestA $valueA, public TestB $valueB)
    {
    }
}
