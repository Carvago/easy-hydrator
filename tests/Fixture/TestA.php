<?php

declare(strict_types=1);

namespace Symplify\EasyHydrator\Tests\Fixture;

class TestA implements TestInterface
{
    public function __construct(public string $value)
    {
    }
}
