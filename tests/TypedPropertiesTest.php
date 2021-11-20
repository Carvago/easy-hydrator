<?php

declare(strict_types=1);

namespace Symplify\EasyHydrator\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symplify\EasyHydrator\ArrayToValueObjectHydrator;
use Symplify\EasyHydrator\Tests\Fixture\TypedProperty;

final class TypedPropertiesTest extends KernelTestCase
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
        $typedProperty = $this->arrayToValueObjectHydrator->hydrateArray(['value' => 'yay'], TypedProperty::class);

        $this->assertInstanceOf(TypedProperty::class, $typedProperty);

        /** @var TypedProperty $typedProperty */
        $this->assertSame('yay', $typedProperty->getValue());
    }
}
