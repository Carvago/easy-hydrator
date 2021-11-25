<?php

declare(strict_types=1);

namespace EAG\EasyHydrator\Tests\TypeCaster;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use EAG\EasyHydrator\Contract\TypeCasterInterface;
use EAG\EasyHydrator\Tests\Fixture\TestA;
use EAG\EasyHydrator\TypeCaster\DateTimeTypeCaster;
use EAG\EasyHydrator\TypeDefinition;
use Exception;
use Generator;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class DateTimeTypeCasterTest extends TestCase
{
    /**
     * @dataProvider isSupportedCases
     */
    public function testIsSupported(TypeDefinition $typeDefinition, bool $expected): void
    {
        self::assertSame($expected, (new DateTimeTypeCaster())->isSupported($typeDefinition), 'Definition ' . $typeDefinition);
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

        yield [new TypeDefinition([TestA::class]), false];

        yield [new TypeDefinition([DateTimeInterface::class]), true];
        yield [new TypeDefinition([DateTime::class]), true];
        yield [new TypeDefinition([DateTimeImmutable::class]), true];

        yield [new TypeDefinition(['callable']), false];
        yield [new TypeDefinition(['iterable']), false];
        yield [new TypeDefinition(['mixed']), false];
        yield [new TypeDefinition(['object']), false];
    }

    /**
     * @dataProvider retypeCases
     */
    public function testRetype(mixed $value, TypeDefinition $typeDefinition, string $expected): void
    {
        $rootTypeCaster = $this->createMock(TypeCasterInterface::class);
        $rootTypeCaster->expects(self::never())
            ->method('retype');

        $actual = (new DateTimeTypeCaster())->retype($value, $typeDefinition, $rootTypeCaster);

        self::assertInstanceOf(DateTime::class, $actual);

        self::assertEquals($expected, $actual->format(DATE_RFC3339_EXTENDED));
    }

    /**
     * @return Generator<mixed>
     */
    public function retypeCases(): Generator
    {
        $typeDefinition = new TypeDefinition([DateTimeInterface::class]);

        yield ['1.2.2021', $typeDefinition, '2021-02-01T00:00:00.000+00:00'];
        yield ['01-02-2021', $typeDefinition, '2021-02-01T00:00:00.000+00:00'];
        yield ['2021-02-01', $typeDefinition, '2021-02-01T00:00:00.000+00:00'];
        yield ['2021-02-01 01:02:03', $typeDefinition, '2021-02-01T01:02:03.000+00:00'];
        yield ['2021-02-01T01:02:03+01:00', $typeDefinition, '2021-02-01T01:02:03.000+01:00'];
        yield ['2021-02-01T01:02:03.004+01:00', $typeDefinition, '2021-02-01T01:02:03.004+01:00'];
    }

    public function testRetypeToImmutable(): void
    {
        $rootTypeCaster = $this->createMock(TypeCasterInterface::class);
        $rootTypeCaster->expects(self::never())
            ->method('retype');

        $typeDefinition = new TypeDefinition([DateTimeImmutable::class]);

        $actual = (new DateTimeTypeCaster())->retype('2021-02-01T01:02:03.004+01:00', $typeDefinition, $rootTypeCaster);

        self::assertInstanceOf(DateTimeImmutable::class, $actual);

        self::assertEquals('2021-02-01T01:02:03.004+01:00', $actual->format(DATE_RFC3339_EXTENDED));
    }


    /**
     * @dataProvider retypeBypassCases
     */
    public function testRetypeBypass(mixed $value, TypeDefinition $typeDefinition): void
    {
        $rootTypeCaster = $this->createMock(TypeCasterInterface::class);
        $rootTypeCaster->expects(self::never())
            ->method('retype');

        self::assertSame($value, (new DateTimeTypeCaster())->retype($value, $typeDefinition, $rootTypeCaster));
    }

    /**
     * @return Generator<mixed>
     */
    public function retypeBypassCases(): Generator
    {
        yield [new DateTime(), new TypeDefinition([DateTimeInterface::class, 'null'])];
        yield [null, new TypeDefinition([DateTimeInterface::class, 'null'])];
    }

    /**
     * @dataProvider retypeValidationCases
     */
    public function testRetypeValidation(mixed $value, TypeDefinition $typeDefinition, Exception $expectedException): void
    {
        $rootTypeCaster = $this->createMock(TypeCasterInterface::class);

        self::expectExceptionObject($expectedException);

        (new DateTimeTypeCaster())->retype($value, $typeDefinition, $rootTypeCaster);
    }

    /**
     * @return Generator<mixed>
     */
    public function retypeValidationCases(): Generator
    {
        yield [null, new TypeDefinition([DateTimeInterface::class]), new RuntimeException('Expected string, given: NULL')];
    }
}
