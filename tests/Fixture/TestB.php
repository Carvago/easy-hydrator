<?php

declare(strict_types=1);

namespace Symplify\EasyHydrator\Tests\Fixture;

final class TestB implements TestInterface
{
    public function __construct(public int $value)
    {
    }
}
