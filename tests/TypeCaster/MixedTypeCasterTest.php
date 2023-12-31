<?php

declare(strict_types=1);

namespace EAG\EasyHydrator\Tests\TypeCaster;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use EAG\EasyHydrator\Contract\TypeCasterInterface;
use EAG\EasyHydrator\Tests\Fixture\TestA;
use EAG\EasyHydrator\TypeCaster\MixedTypeCaster;
use EAG\EasyHydrator\TypeDefinition;
use Generator;
use PHPUnit\Framework\TestCase;
use stdClass;

final class MixedTypeCasterTest extends TestCase
{
    /**
     * @dataProvider isSupportedCases
     */
    public function testIsSupported(TypeDefinition $typeDefinition, bool $expected): void
    {
        self::assertSame($expected, (new MixedTypeCaster())->isSupported($typeDefinition), 'Definition ' . $typeDefinition);
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

        yield [new TypeDefinition([TestA::class]), false];

        yield [new TypeDefinition([DateTimeInterface::class]), false];
        yield [new TypeDefinition([DateTime::class]), false];
        yield [new TypeDefinition([DateTimeImmutable::class]), false];

        yield [new TypeDefinition(['callable']), false];
        yield [new TypeDefinition(['iterable']), false];
        yield [new TypeDefinition(['mixed']), true];
        yield [new TypeDefinition(['object']), false];
    }

    /**
     * @dataProvider retypeCases
     */
    public function testRetype(mixed $value, TypeDefinition $typeDefinition): void
    {
        $rootTypeCaster = $this->createMock(TypeCasterInterface::class);
        $rootTypeCaster
            ->expects($this->never())
            ->method('retype');

        self::assertSame($value, (new MixedTypeCaster())->retype($value, $typeDefinition, $rootTypeCaster));
    }

    /**
     * @return Generator<mixed>
     */
    public static function retypeCases(): Generator
    {
        yield [new stdClass(), new TypeDefinition(['mixed'])];
        yield ['test', new TypeDefinition(['mixed'])];
        yield [111, new TypeDefinition(['mixed'])];
        yield [111.111, new TypeDefinition(['mixed'])];
        yield [true, new TypeDefinition(['mixed'])];
        yield [false, new TypeDefinition(['mixed'])];
    }
}
