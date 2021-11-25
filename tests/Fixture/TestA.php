<?php

declare(strict_types=1);

namespace EAG\EasyHydrator\Tests\Fixture;

class TestA implements TestInterface
{
    public function __construct(public string $value)
    {
    }
}
