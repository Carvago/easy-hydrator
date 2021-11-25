<?php

declare(strict_types=1);

namespace EAG\EasyHydrator\Tests\TypeCaster;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use EAG\EasyHydrator\Contract\TypeCasterInterface;
use EAG\EasyHydrator\Tests\Fixture\TestA;
use EAG\EasyHydrator\TypeCaster\ScalarTypeCaster;
use EAG\EasyHydrator\TypeDefinition;
use Exception;
use Generator;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ScalarTypeCasterTest extends TestCase
{
    /**
     * @dataProvider isSupportedCases
     */
    public function testIsSupported(TypeDefinition $typeDefinition, bool $expected): void
    {
        self::assertSame($expected, (new ScalarTypeCaster())->isSupported($typeDefinition), 'Definition ' . $typeDefinition);
    }

    /**
     * @return Generator<mixed>
     */
    public function isSupportedCases(): Generator
    {
        yield [new TypeDefinition(['string']), true];
        yield [new TypeDefinition(['int']), true];
        yield [new TypeDefinition(['float']), true];
        yield [new TypeDefinition(['bool']), true];

        yield [new TypeDefinition(['string', 'null']), true];

        yield [new TypeDefinition(['array', 'null'], new TypeDefinition(['string'])), false];
        yield [new TypeDefinition(['array', 'null'], new TypeDefinition(['array', 'null'], new TypeDefinition(['string', 'null']))), false];

        yield [new TypeDefinition([TestA::class]), false];

        yield [new TypeDefinition([DateTimeInterface::class]), false];
        yield [new TypeDefinition([DateTime::class]), false];
        yield [new TypeDefinition([DateTimeImmutable::class]), false];

        yield [new TypeDefinition(['callable']), false];
        yield [new TypeDefinition(['iterable']), false];
        yield [new TypeDefinition(['mixed']), false];
        yield [new TypeDefinition(['object']), false];
    }

    /**
     * @dataProvider retypeCases
     */
    public function testRetype(mixed $value, TypeDefinition $typeDefinition, mixed $expected): void
    {
        $rootTypeCaster = $this->createMock(TypeCasterInterface::class);
        $rootTypeCaster
            ->expects($this->never())
            ->method('retype');

        self::assertSame($expected, (new ScalarTypeCaster())->retype($value, $typeDefinition, $rootTypeCaster));
    }

    /**
     * @return Generator<mixed>
     */
    public function retypeCases(): Generator
    {
        $stringable = new class() {
            public function __toString(): string
            {
                return 'test';
            }
        };

        yield [$stringable, new TypeDefinition(['string']), 'test'];
        yield ['test', new TypeDefinition(['string']), 'test'];
        yield [111, new TypeDefinition(['string']), '111'];
        yield [111.111, new TypeDefinition(['string']), '111.111'];
        yield [true, new TypeDefinition(['string']), '1'];
        yield [false, new TypeDefinition(['string']), '0']; // Bool converted to 1/0

        yield ['111', new TypeDefinition(['int']), 111];
        yield [111, new TypeDefinition(['int']), 111];
        yield [111.000, new TypeDefinition(['int']), 111];
        yield [true, new TypeDefinition(['int']), 1];
        yield [false, new TypeDefinition(['int']), 0];

        yield ['111.111', new TypeDefinition(['float']), 111.111];
        yield ['111.000', new TypeDefinition(['float']), 111.0];
        yield [111, new TypeDefinition(['float']), 111]; // Pass by integers
        yield [111.111, new TypeDefinition(['float']), 111.111];
        yield [111.000, new TypeDefinition(['float']), 111.0];
        yield [true, new TypeDefinition(['float']), 1.0];
        yield [false, new TypeDefinition(['float']), 0.0];

        yield ['111', new TypeDefinition(['bool']), true];
        yield ['111.111', new TypeDefinition(['bool']), true];
        yield ['0', new TypeDefinition(['bool']), false];
        yield ['0.0', new TypeDefinition(['bool']), false];
        yield [111, new TypeDefinition(['bool']), true];
        yield [111.111, new TypeDefinition(['bool']), true];
        yield [0, new TypeDefinition(['bool']), false];
        yield [0.0, new TypeDefinition(['bool']), false];
        yield [true, new TypeDefinition(['bool']), true];
        yield [false, new TypeDefinition(['bool']), false];
    }

    /**
     * @dataProvider retypeBypassCases
     */
    public function testRetypeBypass(mixed $value, TypeDefinition $typeDefinition): void
    {
        $rootTypeCaster = $this->createMock(TypeCasterInterface::class);
        $rootTypeCaster->expects(self::never())
            ->method('retype');

        self::assertSame($value, (new ScalarTypeCaster())->retype($value, $typeDefinition, $rootTypeCaster));
    }

    /**
     * @return Generator<mixed>
     */
    public function retypeBypassCases(): Generator
    {
        yield ['test', new TypeDefinition(['string', 'null'])];
        yield [null, new TypeDefinition(['string', 'null'])];

        yield [111, new TypeDefinition(['int', 'null'])];
        yield [null, new TypeDefinition(['int', 'null'])];

        yield [111.111, new TypeDefinition(['float', 'null'])];
        yield [null, new TypeDefinition(['float', 'null'])];

        yield [true, new TypeDefinition(['bool', 'null'])];
        yield [false, new TypeDefinition(['bool', 'null'])];
        yield [null, new TypeDefinition(['bool', 'null'])];
    }

    /**
     * @dataProvider retypeValidationCases
     */
    public function testRetypeValidation(mixed $value, TypeDefinition $typeDefinition, Exception $expectedException): void
    {
        $rootTypeCaster = $this->createMock(TypeCasterInterface::class);

        self::expectExceptionObject($expectedException);

        (new ScalarTypeCaster())->retype($value, $typeDefinition, $rootTypeCaster);
    }

    /**
     * @return Generator<mixed>
     */
    public function retypeValidationCases(): Generator
    {
        $stringTypeDefinition = new TypeDefinition(['string']);

        yield [[], $stringTypeDefinition, new RuntimeException('Expected scalar or Stringable for string conversion, given: array')];
        yield [new TestA('test'), $stringTypeDefinition, new RuntimeException('Expected scalar or Stringable for string conversion, given: object')];

        $intTypeDefinition = new TypeDefinition(['int']);

        yield [[], $intTypeDefinition, new RuntimeException('Expected scalar for int conversion, given: array')];
        yield ['111a', $intTypeDefinition, new RuntimeException('Expected string only with numbers for int conversion, given value: 111a')];
        yield [111.111, $intTypeDefinition, new RuntimeException('Expected float without fractional part for int conversion, given value: 111.111')];

        $floatTypeDefinition = new TypeDefinition(['float']);

        yield [[], $floatTypeDefinition, new RuntimeException('Expected scalar for float conversion, given: array')];
        yield ['111a', $floatTypeDefinition, new RuntimeException('Expected numeric string for float conversion, given value: 111a')];

        $boolTypeDefinition = new TypeDefinition(['bool']);

        yield [[], $boolTypeDefinition, new RuntimeException('Expected scalar for bool conversion, given: array')];
        yield ['111a', $boolTypeDefinition, new RuntimeException('Expected numeric string for bool conversion, given value: 111a')];
    }
}
