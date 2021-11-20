<?php

declare(strict_types=1);

namespace Symplify\EasyHydrator\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symplify\EasyHydrator\ArrayToValueObjectHydrator;
use Symplify\EasyHydrator\Exception\MissingConstructorException;
use Symplify\EasyHydrator\Tests\Fixture\NoConstructor;

final class MissingConstructorTest extends KernelTestCase
{
    private ArrayToValueObjectHydrator $arrayToValueObjectHydrator;

    protected function setUp(): void
    {
        /** @var ArrayToValueObjectHydrator $arrayToValueObjectHydrator */
        $arrayToValueObjectHydrator = self::getContainer()->get(ArrayToValueObjectHydrator::class);
        $this->arrayToValueObjectHydrator = $arrayToValueObjectHydrator;
    }

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    public function test(): void
    {
        $this->expectException(MissingConstructorException::class);

        $this->arrayToValueObjectHydrator->hydrateArray([
            'key' => 'whatever',
        ], NoConstructor::class);
    }
}
