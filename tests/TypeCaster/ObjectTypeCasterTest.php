<?php

declare(strict_types=1);

namespace EAG\EasyHydrator\Tests\TypeCaster;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use EAG\EasyHydrator\Contract\TypeCasterInterface;
use EAG\EasyHydrator\Tests\Fixture\TestA;
use EAG\EasyHydrator\Tests\Fixture\TestAB;
use EAG\EasyHydrator\Tests\Fixture\TestB;
use EAG\EasyHydrator\TypeCaster\ObjectTypeCaster;
use EAG\EasyHydrator\TypeDefinition;
use Exception;
use Generator;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ObjectTypeCasterTest extends TestCase
{
    /**
     * @dataProvider isSupportedCases
     */
    public function testIsSupported(TypeDefinition $typeDefinition, bool $expected): void
    {
        self::assertSame($expected, (new ObjectTypeCaster())->isSupported($typeDefinition), 'Definition ' . $typeDefinition);
    }

    /**
     * @return Generator<mixed>
     */
    public static function isSupportedCases(): Generator
    {
        yield [new TypeDefinition(['string']), false];
        yield [new TypeDefinition(['int']), false];
        yield [new TypeDefinition(['float']), false];
        yield [new TypeDefinition(['bool']), false];

        yield [new TypeDefinition(['string', 'null']), false];

        yield [new TypeDefinition(['array', 'null'], new TypeDefinition(['string'])), false];
        yield [new TypeDefinition(['array', 'null'], new TypeDefinition(['array', 'null'], new TypeDefinition(['string', 'null']))), false];

        yield [new TypeDefinition([DateTimeInterface::class]), false];
        yield [new TypeDefinition([DateTime::class]), true];
        yield [new TypeDefinition([DateTimeImmutable::class]), true];

        yield [new TypeDefinition(['callable']), false];
        yield [new TypeDefinition(['iterable']), false];
        yield [new TypeDefinition(['mixed']), false];
        yield [new TypeDefinition(['object']), false];
    }

    public function testRetypeA(): void
    {
        $rootTypeCaster = $this->createMock(TypeCasterInterface::class);
        $rootTypeCaster
            ->expects($this->exactly(1))
            ->method('retype')
            ->with('test')
            ->willReturnOnConsecutiveCalls('test');

        $value = ['value' => 'test'];
        $typeDefinition = new TypeDefinition([TestA::class]);
        $expected = new TestA('test');

        self::assertEquals($expected, (new ObjectTypeCaster())->retype($value, $typeDefinition, $rootTypeCaster));
    }

    public function testRetypeB(): void
    {
        $rootTypeCaster = $this->createMock(TypeCasterInterface::class);
        $rootTypeCaster
            ->expects($this->exactly(2))
            ->method('retype')
            ->willReturnOnConsecutiveCalls(
                new TestA('test'),
                new TestB(100),
            );

        $value = [
            'valueA' => ['value' => 'test'],
            'valueB' => ['value' => 100],
        ];
        $typeDefinition = new TypeDefinition([TestAB::class]);
        $expected = new TestAB(new TestA('test'), new TestB(100));

        self::assertEquals($expected, (new ObjectTypeCaster())->retype($value, $typeDefinition, $rootTypeCaster));
    }

    /**
     * @dataProvider retypeBypassCases
     */
    public function testRetypeBypass(mixed $value, TypeDefinition $typeDefinition): void
    {
        $rootTypeCaster = $this->createMock(TypeCasterInterface::class);
        $rootTypeCaster->expects(self::never())
            ->method('retype');

        self::assertSame($value, (new ObjectTypeCaster())->retype($value, $typeDefinition, $rootTypeCaster));
    }

    /**
     * @return Generator<mixed>
     */
    public static function retypeBypassCases(): Generator
    {
        yield [new TestA('test'), new TypeDefinition([TestA::class, 'null'])];
        yield [null, new TypeDefinition([TestA::class, 'null'])];
    }

    /**
     * @dataProvider retypeValidationCases
     */
    public function testRetypeValidation(mixed $value, TypeDefinition $typeDefinition, Exception $expectedException): void
    {
        $rootTypeCaster = $this->createMock(TypeCasterInterface::class);

        self::expectExceptionObject($expectedException);

        (new ObjectTypeCaster())->retype($value, $typeDefinition, $rootTypeCaster);
    }

    /**
     * @return Generator<mixed>
     */
    public static function retypeValidationCases(): Generator
    {
        yield [null, new TypeDefinition([DateTimeInterface::class]), new RuntimeException('Expected array, given: NULL')];
    }
}
