<?php

declare(strict_types=1);

namespace Symplify\EasyHydrator\Tests\TypeCaster;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use Generator;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symplify\EasyHydrator\Contract\TypeCasterInterface;
use Symplify\EasyHydrator\Tests\Fixture\TestA;
use Symplify\EasyHydrator\Tests\Fixture\TestAB;
use Symplify\EasyHydrator\Tests\Fixture\TestB;
use Symplify\EasyHydrator\TypeCaster\ObjectTypeCaster;
use Symplify\EasyHydrator\TypeDefinition;

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
    public function isSupportedCases(): Generator
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
    }

    /**
     * @dataProvider retypeCases
     */
    public function testRetype(mixed $value, TypeDefinition $typeDefinition, object $expected, TypeCasterInterface $rootTypeCaster): void
    {
        self::assertEquals($expected, (new ObjectTypeCaster())->retype($value, $typeDefinition, $rootTypeCaster));
    }

    /**
     * @return Generator<mixed>
     */
    public function retypeCases(): Generator
    {
        $rootTypeCaster = $this->createMock(TypeCasterInterface::class);
        $rootTypeCaster
            ->expects($this->exactly(1))
            ->method('retype')
            ->withConsecutive(['test'])
            ->willReturnOnConsecutiveCalls('test');

        yield [
            ['value' => 'test'],
            new TypeDefinition([TestA::class]),
            new TestA('test'),
            $rootTypeCaster,
        ];

        $rootTypeCaster = $this->createMock(TypeCasterInterface::class);
        $rootTypeCaster
            ->expects($this->exactly(2))
            ->method('retype')
            ->withConsecutive(
                [['value' => 'test']],
                [['value' => 100]],
            )
            ->willReturnOnConsecutiveCalls(
                new TestA('test'),
                new TestB(100),
            );

        yield [
            [
                'valueA' => ['value' => 'test'],
                'valueB' => ['value' => 100],
            ],
            new TypeDefinition([TestAB::class]),
            new TestAB(new TestA('test'), new TestB(100)),
            $rootTypeCaster,
        ];
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
    public function retypeBypassCases(): Generator
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
    public function retypeValidationCases(): Generator
    {
        yield [null, new TypeDefinition([DateTimeInterface::class]), new RuntimeException('Expected array, given: NULL')];
    }
}
