<?php

declare(strict_types=1);

namespace EAG\EasyHydrator\Tests;

use DateTimeInterface;
use EAG\EasyHydrator\Tests\Fixture\TestA;
use EAG\EasyHydrator\TypeDefinition;
use EAG\EasyHydrator\TypeDefinitionBuilder;
use Generator;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;

final class TypeDefinitionBuilderTest extends TestCase
{
    /**
     * @dataProvider buildCases
     */
    public function testBuild(callable|ReflectionParameter $callbackOrParameter, TypeDefinition $expectedTypeDefinition): void
    {
        $reflectionParameters = [$callbackOrParameter];
        if (!$callbackOrParameter instanceof ReflectionParameter) {
            $reflectionParameters = (new ReflectionFunction($callbackOrParameter))->getParameters();
        }

        self::assertGreaterThanOrEqual(1, $reflectionParameters);

        foreach ($reflectionParameters as $reflectionParameter) {
            $typeDefinition = TypeDefinitionBuilder::create($reflectionParameter)->build();

            $this->assertEquals($expectedTypeDefinition, $typeDefinition, 'Parameter: ' . $reflectionParameter->getName());
        }
    }

    /**
     * @param TestA $value
     */
    private function buildCasePhpDocClassResolve($value)
    {
    }

    /**
     * @return Generator<mixed>
     */
    public static function buildCases(): Generator
    {
        yield 'string' => [
            fn(string $value) => null,
            new TypeDefinition(['string']),
        ];

        yield '?string' => [
            fn(?string $value) => null,
            new TypeDefinition(['string', 'null']),
        ];

        yield 'typehint union sort' => [
            fn(float|null|bool|int|string|DateTimeInterface $value) => null,
            // Union reflection has stable order
            new TypeDefinition([DateTimeInterface::class, 'string', 'int', 'float', 'bool', 'null']),
        ];

        yield 'phpdoc union sort' => [
            /** @param float|null|bool|int|string|DateTimeInterface $value */
            fn($value) => null,
            new TypeDefinition(['float', 'null', 'bool', 'int', 'string', DateTimeInterface::class]),
        ];

        yield 'typehint class' => [
            /**
             * @param TestA $value2
             * @param \EAG\EasyHydrator\Tests\Fixture\TestA $value3
             *
             */
            fn(TestA $value1, $value2, $value3) => null,
            new TypeDefinition([TestA::class]),
        ];

        $anonymousClass = new class(null) {
            /**
             * @param \EAG\EasyHydrator\Tests\Fixture\TestA $value
             */
            public function __construct($value)
            {
            }
        };

        yield 'phpdoc anonymous class use resolve' => [
            (new \ReflectionClass($anonymousClass))->getConstructor()->getParameters()[0],
            new TypeDefinition([TestA::class]),
        ];

        yield 'phpdoc class method use resolve' => [
            (new ReflectionMethod(self::class, 'buildCasePhpDocClassResolve'))->getParameters()[0],
            new TypeDefinition([TestA::class]),
        ];

        yield 'float/int support' => [
            fn(float $value) => null,
            new TypeDefinition(['float', 'int']),
        ];

        yield 'array of strings' => [
            /**
             * @param array<string> $value1
             * @param array<string> $value2
             * @param array<int,string> $value3
             * @param string[] $value4
             * @param string[] $value5
             */
            fn(array $value1, $value2, $value3, array $value4, $value5) => null,
            new TypeDefinition(['array'], new TypeDefinition(['string'])),
        ];

        yield 'array of array of strings' => [
            /**
             * @param array<array<string>> $value1
             * @param array<array<string>> $value2
             * @param array<int,array<int,string>> $value3
             * @param string[][] $value4
             * @param string[][] $value5
             */
            fn(array $value1, $value2, $value3, array $value4, $value5) => null,
            new TypeDefinition(['array'], new TypeDefinition(['array'], new TypeDefinition(['string']))),
        ];

        yield 'array of array of strings all nullables' => [
            /**
             * @param array<array<string|null>|null>|null $value1
             * @param array<array<string|null>|null>|null $value2
             * @param array<int,array<int,string|null>|null>|null $value3
             */
            fn(array $value1, $value2, $value3) => null,
            new TypeDefinition(['array', 'null'], new TypeDefinition(['array', 'null'], new TypeDefinition(['string', 'null']))),
        ];

        yield 'array of array of strings all nullables ([] syntax)' => [
            /**
             * @param string[][]|null $value1
             * @param string[][]|null $value2
             */
            fn(array $value1, $value2) => null,
            new TypeDefinition(['array', 'null'], new TypeDefinition(['array'], new TypeDefinition(['string']))),
        ];
    }
}
