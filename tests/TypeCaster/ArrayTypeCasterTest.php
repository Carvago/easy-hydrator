<?php

declare(strict_types=1);

namespace EAG\EasyHydrator\Tests\TypeCaster;

use EAG\EasyHydrator\Contract\TypeCasterInterface;
use EAG\EasyHydrator\Tests\Fixture\TestA;
use EAG\EasyHydrator\TypeCaster\ArrayTypeCaster;
use EAG\EasyHydrator\TypeDefinition;
use Exception;
use Generator;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ArrayTypeCasterTest extends TestCase
{
    /**
     * @dataProvider isSupportedCases
     */
    public function testIsSupported(TypeDefinition $typeDefinition, bool $expected): void
    {
        self::assertSame($expected, (new ArrayTypeCaster())->isSupported($typeDefinition), 'Definition ' . $typeDefinition);
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

        yield [new TypeDefinition(['array', 'null'], new TypeDefinition(['string'])), true];
        yield [new TypeDefinition(['array', 'null'], new TypeDefinition(['array', 'null'], new TypeDefinition(['string', 'null']))), true];

        yield [new TypeDefinition([TestA::class]), false];
    }

    /**
     * @dataProvider retypeCases
     */
    public function testRetype(mixed $value, TypeDefinition $typeDefinition, mixed $expected): void
    {
        $rootTypeCaster = $this->createMock(TypeCasterInterface::class);
        $rootTypeCaster->expects(self::any())
            ->method('retype')
            ->willReturnArgument(0);

        self::assertSame($expected, (new ArrayTypeCaster())->retype($value, $typeDefinition, $rootTypeCaster));
    }

    /**
     * @return Generator<mixed>
     */
    public function retypeCases(): Generator
    {
        $typeDefinition = new TypeDefinition(['array'], new TypeDefinition(['string']));

        yield [['test1', 'test2'], $typeDefinition, ['test1', 'test2']];
        yield [[['test1', 'test2']], $typeDefinition, [['test1', 'test2']]];
        yield [['test1' => 'test1', 'test2' => 'test2'], $typeDefinition, ['test1' => 'test1', 'test2' => 'test2']];
    }

    /**
     * @dataProvider retypeBypassCases
     */
    public function testRetypeBypass(mixed $value, TypeDefinition $typeDefinition): void
    {
        $rootTypeCaster = $this->createMock(TypeCasterInterface::class);
        $rootTypeCaster->expects(self::never())
            ->method('retype');

        self::assertSame($value, (new ArrayTypeCaster())->retype($value, $typeDefinition, $rootTypeCaster));
    }

    /**
     * @return Generator<mixed>
     */
    public function retypeBypassCases(): Generator
    {
        yield [[], new TypeDefinition(['array', 'null'], new TypeDefinition(['string']))];
        yield [null, new TypeDefinition(['array', 'null'], new TypeDefinition(['string']))];
    }

    /**
     * @dataProvider retypeValidationCases
     */
    public function testRetypeValidation(mixed $value, TypeDefinition $typeDefinition, Exception $expectedException): void
    {
        $rootTypeCaster = $this->createMock(TypeCasterInterface::class);

        self::expectExceptionObject($expectedException);

        (new ArrayTypeCaster())->retype($value, $typeDefinition, $rootTypeCaster);
    }

    /**
     * @return Generator<mixed>
     */
    public function retypeValidationCases(): Generator
    {
        yield [null, new TypeDefinition(['array'], new TypeDefinition(['string'])), new RuntimeException('Expected array, given: NULL')];
    }
}
