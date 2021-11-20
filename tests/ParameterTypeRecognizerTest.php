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
        bool $expectedIsParameterOfTypeString,
        bool $expectedIsParameterOfTypePerson,
        bool $expectedIsParameterOfTypeArray,
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
            self::assertSame($expectedIsParameterOfTypeString, $parameterTypeRecognizer->isParameterOfType($reflectionParameter, 'string'));
            self::assertSame($expectedIsParameterOfTypePerson, $parameterTypeRecognizer->isParameterOfType($reflectionParameter, Person::class));
            self::assertSame($expectedIsParameterOfTypeArray, $parameterTypeRecognizer->isParameterOfType($reflectionParameter, 'array'));
        }
    }

    /**
     * @return Generator<mixed>
     */
    public function checkCases(): Generator
    {
        yield 'string typed' => [
            'object' => new class() {
                public function test(string $value) {}
            },
            'type' => 'string',
            'typeFromDocBlock' => null,
            'isArray' => false,
            'arrayLevels' => 0,
            'isParameterOfTypeString' => true,
            'isParameterOfTypePerson' => false,
            'isParameterOfTypeArray' => false,
        ];
        yield 'string doc' => [
            'object' => new class() {
                /** @param string $value */
                public function test($value) {}
            },
            'type' => 'string',
            'typeFromDocBlock' => 'string',
            'isArray' => false,
            'arrayLevels' => 0,
            'isParameterOfTypeString' => true,
            'isParameterOfTypePerson' => false,
            'isParameterOfTypeArray' => false,
        ];
        yield 'string array' => [
            'object' => new class() {
                /** @param array<string> $value */
                public function test1(array $value) {}
                /** @param array<string>|null $value */
                public function test2(?array $value) {}
                /** @param string[]|null $value */
                public function test3(?array $value) {}
                /** @param string[] $value */
                public function test4(array $value) {}
            },
            'type' => 'array',
            'typeFromDocBlock' => 'string',
            'isArray' => true,
            'arrayLevels' => 1,
            'isParameterOfTypeString' => true,
            'isParameterOfTypePerson' => false,
            'isParameterOfTypeArray' => true,
        ];
        yield 'non-typed string array' => [
            'object' => new class() {
                /** @param array<string>|null $value */
                public function test1($value) {}
                /** @param array<int,string> $value */
                public function test2($value) {}
                /** @param array<int,string>|null $value */
                public function test3($value) {}
            },
            'type' => 'string',  // TODO Should be array?
            'typeFromDocBlock' => 'string',
            'isArray' => true,
            'arrayLevels' => 1,
            'isParameterOfTypeString' => true,
            'isParameterOfTypePerson' => false,
            'isParameterOfTypeArray' => false, // TODO Should be true?
        ];
        yield 'class array' => [
            'object' => new class() {
                /** @param array<\Symplify\EasyHydrator\Tests\Fixture\Person> $value */
                public function test1(?array $value) {}
                /** @param array<\Symplify\EasyHydrator\Tests\Fixture\Person>|null $value */
                public function test2(?array $value) {}
                /** @param \Symplify\EasyHydrator\Tests\Fixture\Person[]|null $value */
                public function test3(?array $value) {}
                /** @param \Symplify\EasyHydrator\Tests\Fixture\Person[] $value */
                public function test4(array $value) {}
                /** @param array<int,\Symplify\EasyHydrator\Tests\Fixture\Person> $value */
                public function test5(?array $value) {}
                /** @param array<int,\Symplify\EasyHydrator\Tests\Fixture\Person>|null $value */
                public function test6(?array $value) {}
            },
            'type' => 'array',
            'typeFromDocBlock' => Person::class,
            'isArray' => true,
            'arrayLevels' => 1,
            'isParameterOfTypeString' => false,
            'isParameterOfTypePerson' => true,
            'isParameterOfTypeArray' => true,
        ];
        yield 'union type priorities' => [
            'object' => new class() {
                public function test1(null|string|Person $value) {}
                public function test2(string|null|Person $value) {}
                public function test3(Person|string|null $value) {}
            },
            'type' => Person::class, // ReflectionUnionType->getTypes() moves classed to the beginning
            'typeFromDocBlock' => null,
            'isArray' => false,
            'arrayLevels' => 0,
            'isParameterOfTypeString' => false,
            'isParameterOfTypePerson' => true,
            'isParameterOfTypeArray' => false,
        ];
        yield 'nested arrays' => [
            'object' => new class() {
                /** @param array<array<string>> $value */
                public function test1(array $value) {}
                /** @param array<array<string>>|null $value */
                public function test2(?array $value) {}
                /** @param string[][] $value */
                public function test3(array $value) {}
                /** @param string[][]|null $value */
                public function test4(?array $value) {}
                /** @param array<array<int,string>> $value */
                public function test5(array $value) {}
                /** @param array<array<int,string>>|null $value */
                public function test6(?array $value) {}
            },
            'type' => 'array',
            'typeFromDocBlock' => 'string',
            'isArray' => true,
            'arrayLevels' => 2,
            'isParameterOfTypeString' => true,
            'isParameterOfTypePerson' => false,
            'isParameterOfTypeArray' => true,
        ];
    }
}
