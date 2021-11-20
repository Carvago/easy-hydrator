<?php

declare(strict_types=1);

namespace Symplify\EasyHydrator\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symplify\EasyHydrator\ArrayToValueObjectHydrator;
use Symplify\EasyHydrator\Exception\MissingDataException;
use Symplify\EasyHydrator\Tests\Fixture\DefaultValuesConstructor;

final class DefaultValuesHydratorTest extends KernelTestCase
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

    public function testExceptionWillBeThrownWhenMissingDataForNonOptionalParameter(): void
    {
        $this->expectException(MissingDataException::class);

        $this->arrayToValueObjectHydrator->hydrateArray([], DefaultValuesConstructor::class);
    }

    public function testDefaultValues(): void
    {
        $data = [
            'foo' => null,
            'bar' => 'baz',
        ];

        /** @var DefaultValuesConstructor $object */
        $object = $this->arrayToValueObjectHydrator->hydrateArray($data, DefaultValuesConstructor::class);

        $this->assertNull($object->getFoo());
        $this->assertNull($object->getPerson());
        $this->assertSame('baz', $object->getBar());
    }
}
