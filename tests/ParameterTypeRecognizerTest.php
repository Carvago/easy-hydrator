<?php

declare(strict_types=1);

namespace Symplify\EasyHydrator\Tests;

use Generator;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symplify\EasyHydrator\ParameterTypeRecognizer;
use Symplify\EasyHydrator\Tests\Fixture\Person;

final class ParameterTypeRecognizerTest extends TestCase
{
    /**
     * @dataProvider checkCases
     */
    public function test(
        object $object,
        ?string $expectedType,
        ?string $expectedTypeFromDocBlock,
        bool $expectedIsArray,
        int $expectedArrayLevels,
    ): void {
        $parameterTypeRecognizer = new ParameterTypeRecognizer();

        $reflectionClass = new ReflectionClass($object);

        self::assertGreaterThanOrEqual(1, count($reflectionClass->getMethods()));

        // Better to use only one parameter in one method, but it looks more readable with keeping similar cases together
        foreach ((new ReflectionClass($object))->getMethods() as $reflectionMethod) {
            self::assertCount(1, $reflectionMethod->getParameters());

            $reflectionParameter = $reflectionMethod->getParameters()[0];

            self::assertSame($expectedType, $parameterTypeRecognizer->getType($reflectionParameter));
            self::assertSame($expectedTypeFromDocBlock, $parameterTypeRecognizer->getTypeFromDocBlock($reflectionParameter));
            self::assertSame($expectedIsArray, $parameterTypeRecognizer->isArray($reflectionParameter));
            self::assertSame($expectedArrayLevels, $parameterTypeRecognizer->getArrayLevels($reflectionParameter));
        }
    }

    /**
     * @return Generator<mixed>
     */
    public function checkCases(): Generator
    {
        yield [
            'object' => new class() {
                public function test(string $value) {}
            },
            'expectedType' => 'string',
            'expectedTypeFromDocBlock' => null,
            'expectedIsArray' => false,
            'expectedArrayLevels' => 0,
        ];
        yield [
            'object' => new class() {
                /** @param string $value */
                public function test($value) {}
            },
            'expectedType' => 'string',
            'expectedTypeFromDocBlock' => 'string',
            'expectedIsArray' => false,
            'expectedArrayLevels' => 0,
        ];
        yield [
            'object' => new class() {
                /** @param array<string> $value */
                public function test1(array $value) {}
                /** @param array<string>|null $value */
                public function test2(?array $value) {}
                /** @param string[]|null $value */
                public function test3(?array $value) {}
            },
            'expectedType' => 'array',
            'expectedTypeFromDocBlock' => 'string',
            'expectedIsArray' => true,
            'expectedArrayLevels' => 0, // TODO Should be 1 as for string[] syntax
        ];
        yield [
            'object' => new class() {
                /** @param array<string>|null $value */
                public function test($value) {}
            },
            'expectedType' => 'string',
            'expectedTypeFromDocBlock' => 'string',
            'expectedIsArray' => false, // TODO Should be true
            'expectedArrayLevels' => 0, // TODO Should be 1 as for string[] syntax
        ];
        yield [
            'object' => new class() {
                /** @param string[] $value */
                public function test(array $value) {}
            },
            'expectedType' => 'array',
            'expectedTypeFromDocBlock' => 'string',
            'expectedIsArray' => true,
            'expectedArrayLevels' => 1,
        ];
        yield [
            'object' => new class() {
                /** @param array<int,string> $value */
                public function test($value) {}
            },
            'expectedType' => 'string', // TODO Should be array
            'expectedTypeFromDocBlock' => 'string',
            'expectedIsArray' => true,
            'expectedArrayLevels' => 0, // TODO Should be 1 as for string[] syntax
        ];
        yield [
            'object' => new class() {
                /** @param array<int,string>|null $value */
                public function test($value) {}
            },
            'expectedType' => 'string', // TODO Should be array
            'expectedTypeFromDocBlock' => 'string',
            'expectedIsArray' => false, // TODO Should be true
            'expectedArrayLevels' => 0, // TODO Should be 1 as for string[] syntax
        ];
        yield [
            'object' => new class() {
                /** @param array<\Symplify\EasyHydrator\Tests\Fixture\Person> $value */
                public function test1(?array $value) {}
                /** @param array<\Symplify\EasyHydrator\Tests\Fixture\Person>|null $value */
                public function test2(?array $value) {}
                /** @param \Symplify\EasyHydrator\Tests\Fixture\Person[]|null $value */
                public function test3(?array $value) {}
            },
            'expectedType' => 'array',
            'expectedTypeFromDocBlock' => Person::class,
            'expectedIsArray' => true,
            'expectedArrayLevels' => 0, // TODO Should be 1 as for string[] syntax
        ];
        yield [
            'object' => new class() {
                /** @param \Symplify\EasyHydrator\Tests\Fixture\Person[] $value */
                public function test(array $value) {}
            },
            'expectedType' => 'array',
            'expectedTypeFromDocBlock' => Person::class,
            'expectedIsArray' => true,
            'expectedArrayLevels' => 1,
        ];
        yield [
            'object' => new class() {
                /** @param array<int,\Symplify\EasyHydrator\Tests\Fixture\Person> $value */
                public function test1(?array $value) {}
                /** @param array<int,\Symplify\EasyHydrator\Tests\Fixture\Person>|null $value */
                public function test2(?array $value) {}
            },
            'expectedType' => 'array',
            'expectedTypeFromDocBlock' => Person::class,
            'expectedIsArray' => true,
            'expectedArrayLevels' => 0, // TODO Should be 1 as for string[] syntax
        ];
        yield [
            'object' => new class() {
                public function test1(null|string|Person $value) {}
                public function test2(string|null|Person $value) {}
                public function test3(Person|string|null $value) {}
            },
            'expectedType' => Person::class, // ReflectionUnionType->getTypes() moves classed to the beginning
            'expectedTypeFromDocBlock' => null,
            'expectedIsArray' => false,
            'expectedArrayLevels' => 0,
        ];
    }
}
