<?php

declare(strict_types=1);

namespace EAG\EasyHydrator\Tests;

use EAG\EasyHydrator\Tests\Fixture\TestA;
use EAG\EasyHydrator\Tests\Fixture\TestAExtended;
use EAG\EasyHydrator\Tests\Fixture\TestInterface;
use EAG\EasyHydrator\TypeDefinition;
use Exception;
use Generator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TypeDefinitionTest extends TestCase
{
    /**
     * @dataProvider supportsCases
     */
    public function testSupports(TypeDefinition $typeDefinition, string $type, bool $expected): void
    {
        self::assertSame($expected, $typeDefinition->supports($type), 'Definition ' . $typeDefinition . ' type ' . $type);
        self::assertSame($expected, $typeDefinition->supportsAnyOf($type), 'Definition ' . $typeDefinition . ' type ' . $type);
    }

    /**
     * @return Generator<mixed>
     */
    public function supportsCases(): Generator
    {
        yield [new TypeDefinition(['string']), 'string', true];
        yield [new TypeDefinition(['string']), 'mixed', false];

        yield [new TypeDefinition(['int']), 'int', true];
        yield [new TypeDefinition(['int']), 'mixed', false];

        yield [new TypeDefinition(['float']), 'float', true];
        yield [new TypeDefinition(['float']), 'mixed', false];

        yield [new TypeDefinition(['bool']), 'bool', true];
        yield [new TypeDefinition(['bool']), 'mixed', false];

        yield [new TypeDefinition(['string', 'null']), 'null', true];

        yield [new TypeDefinition(['array', 'null'], new TypeDefinition(['string'])), 'array', true];
        yield [new TypeDefinition(['array', 'null'], new TypeDefinition(['string'])), 'null', true];
        yield [new TypeDefinition(['array', 'null'], new TypeDefinition(['string'])), 'mixed', false];

        yield [new TypeDefinition([TestA::class]), TestA::class, true];
        yield [new TypeDefinition([TestA::class]), TestAExtended::class, true];

        yield [new TypeDefinition([TestAExtended::class]), TestAExtended::class, true];

        yield [new TypeDefinition([TestInterface::class]), TestInterface::class, true];
        yield [new TypeDefinition([TestInterface::class]), TestAExtended::class, true];
        yield [new TypeDefinition([TestInterface::class]), TestA::class, true];

        yield [new TypeDefinition([TestA::class]), TestInterface::class, false];
        yield [new TypeDefinition([TestAExtended::class]), TestA::class, false];
        yield [new TypeDefinition([TestAExtended::class]), TestInterface::class, false];
    }

    public function testSupportsNull(): void
    {
        $typeDefinition = new TypeDefinition(['string', 'null']);
        self::assertTrue($typeDefinition->supportsNull());

        $typeDefinition = new TypeDefinition(['string']);
        self::assertFalse($typeDefinition->supportsNull());
    }

    /**
     * @dataProvider supportsValueCases
     */
    public function testSupportsValue(TypeDefinition $typeDefinition, mixed $value, bool $expected): void
    {
        self::assertSame($expected, $typeDefinition->supportsValue($value), 'Definition ' . $typeDefinition . ' value ' . var_export($value, true));
    }

    /**
     * @return Generator<mixed>
     */
    public function supportsValueCases(): Generator
    {
        yield [new TypeDefinition(['array'], new TypeDefinition(['string'])), new TestA('test'), false];
        yield [new TypeDefinition(['array'], new TypeDefinition(['string'])), [], true];
        yield [new TypeDefinition(['array'], new TypeDefinition(['string'])), 'test', false];
        yield [new TypeDefinition(['array'], new TypeDefinition(['string'])), 123, false];
        yield [new TypeDefinition(['array'], new TypeDefinition(['string'])), 1.23, false];
        yield [new TypeDefinition(['array'], new TypeDefinition(['string'])), true, false];
        yield [new TypeDefinition(['array'], new TypeDefinition(['string'])), null, false];
        yield [new TypeDefinition(['array', 'null'], new TypeDefinition(['string'])), null, true];

        yield [new TypeDefinition(['string']), new TestA('test'), false];
        yield [new TypeDefinition(['string']), [], false];
        yield [new TypeDefinition(['string']), 'test', true];
        yield [new TypeDefinition(['string']), 123, false];
        yield [new TypeDefinition(['string']), 1.23, false];
        yield [new TypeDefinition(['string']), true, false];
        yield [new TypeDefinition(['string']), null, false];
        yield [new TypeDefinition(['string', 'null']), null, true];

        yield [new TypeDefinition(['int']), new TestA('test'), false];
        yield [new TypeDefinition(['int']), [], false];
        yield [new TypeDefinition(['int']), 'test', false];
        yield [new TypeDefinition(['int']), 123, true];
        yield [new TypeDefinition(['int']), 1.23, false];
        yield [new TypeDefinition(['int']), true, false];
        yield [new TypeDefinition(['int']), null, false];
        yield [new TypeDefinition(['int', 'null']), null, true];

        yield [new TypeDefinition(['float']), new TestA('test'), false];
        yield [new TypeDefinition(['float']), [], false];
        yield [new TypeDefinition(['float']), 'test', false];
        yield [new TypeDefinition(['float']), 123, true]; // Supports integers too
        yield [new TypeDefinition(['float']), 1.23, true];
        yield [new TypeDefinition(['float']), true, false];
        yield [new TypeDefinition(['float']), null, false];
        yield [new TypeDefinition(['float', 'null']), null, true];

        yield [new TypeDefinition(['bool']), new TestA('test'), false];
        yield [new TypeDefinition(['bool']), [], false];
        yield [new TypeDefinition(['bool']), 'test', false];
        yield [new TypeDefinition(['bool']), 123, false];
        yield [new TypeDefinition(['bool']), 1.23, false];
        yield [new TypeDefinition(['bool']), true, true];
        yield [new TypeDefinition(['bool']), null, false];
        yield [new TypeDefinition(['bool', 'null']), null, true];

        yield [new TypeDefinition(['bool']), new TestA('test'), false];
        yield [new TypeDefinition(['bool']), [], false];
        yield [new TypeDefinition(['bool']), 'test', false];
        yield [new TypeDefinition(['bool']), 123, false];
        yield [new TypeDefinition(['bool']), 1.23, false];
        yield [new TypeDefinition(['bool']), true, true];
        yield [new TypeDefinition(['bool']), null, false];
        yield [new TypeDefinition(['bool', 'null']), null, true];

        yield [new TypeDefinition([TestA::class]), new TestA('test'), true];
        yield [new TypeDefinition([TestA::class]), [], false];
        yield [new TypeDefinition([TestA::class]), 'test', false];
        yield [new TypeDefinition([TestA::class]), 123, false];
        yield [new TypeDefinition([TestA::class]), 1.23, false];
        yield [new TypeDefinition([TestA::class]), true, false];
        yield [new TypeDefinition([TestA::class]), null, false];
        yield [new TypeDefinition([TestA::class, 'null']), null, true];
    }

    public function testSupportsAnyOf(): void
    {
        $typeDefinition = new TypeDefinition(['string', 'int', 'null']);
        self::assertTrue($typeDefinition->supportsAnyOf('bool', 'int'));

        $typeDefinition = new TypeDefinition(['string', 'int', 'null']);
        self::assertFalse($typeDefinition->supportsAnyOf('bool', 'float'));
    }

    public function testGetFirstAvailableType(): void
    {
        $typeDefinition = new TypeDefinition(['string', 'int', 'null']);
        self::assertSame('string', $typeDefinition->getFirstAvailableType());

        $typeDefinition = new TypeDefinition(['null', 'int', 'string']);
        self::assertSame('int', $typeDefinition->getFirstAvailableType());
    }

    /**
     * @dataProvider validationCases
     */
    public function testValidation(array $typeDefinition, ?TypeDefinition $innerType, Exception $expectedException): void
    {
        self::expectExceptionObject($expectedException);

        new TypeDefinition($typeDefinition, $innerType);
    }

    /**
     * @return Generator<mixed>
     */
    public function validationCases(): Generator
    {
        yield [[], null, new InvalidArgumentException('List of types could not be empty')];
        yield [['null'], null, new InvalidArgumentException('List of types should contain at least on type along with null')];
        yield [['array'], null, new InvalidArgumentException('Type supports array, but have no specified inner type definition')];
        yield [['string'], new TypeDefinition(['string']), new InvalidArgumentException('Type has inner type definition, but does not support array')];
        yield [['invalid'], null, new InvalidArgumentException('Provided value is not a type or class/interface: invalid')];
    }

    /**
     * @dataProvider toStringCases
     */
    public function testToString(TypeDefinition $typeDefinition, string $expected): void
    {
        self::assertSame($expected, (string) $typeDefinition);
    }

    /**
     * @return Generator<mixed>
     */
    public function toStringCases(): Generator
    {
        yield [new TypeDefinition(['string']), 'string'];
        yield [new TypeDefinition(['int']), 'int'];
        yield [new TypeDefinition(['float']), 'float'];
        yield [new TypeDefinition(['bool']), 'bool'];

        yield [new TypeDefinition(['string', 'null']), 'string|null'];

        yield [new TypeDefinition([TestA::class]), 'EAG\EasyHydrator\Tests\Fixture\TestA'];
        yield [new TypeDefinition([TestA::class, 'null']), 'EAG\EasyHydrator\Tests\Fixture\TestA|null'];

        yield [new TypeDefinition(['array'], new TypeDefinition(['string'])), 'array<string>'];
        yield [new TypeDefinition(['array'], new TypeDefinition(['array'], new TypeDefinition(['string']))), 'array<array<string>>'];
        yield [new TypeDefinition(['array', 'null'], new TypeDefinition(['array', 'null'], new TypeDefinition(['string', 'null']))), 'array<array<string|null>|null>|null'];
    }
}
