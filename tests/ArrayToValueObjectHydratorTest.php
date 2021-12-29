<?php

declare(strict_types=1);

namespace EAG\EasyHydrator\Tests;

use DateTime;
use DateTimeImmutable;
use EAG\EasyHydrator\ArrayToValueObjectHydrator;
use EAG\EasyHydrator\Exception\MissingConstructorException;
use EAG\EasyHydrator\Exception\MissingDataException;
use EAG\EasyHydrator\Tests\Fixture\NotSet;
use EAG\EasyHydrator\Tests\Fixture\TestA;
use EAG\EasyHydrator\Tests\Fixture\TestAB;
use EAG\EasyHydrator\Tests\Fixture\TestArray;
use EAG\EasyHydrator\Tests\Fixture\TestB;
use EAG\EasyHydrator\Tests\Fixture\TestDateTime;
use EAG\EasyHydrator\Tests\Fixture\TestDefaults;
use EAG\EasyHydrator\Tests\Fixture\TestNoConstructor;
use EAG\EasyHydrator\Tests\Fixture\TestNotSet;
use EAG\EasyHydrator\Tests\Fixture\TestScalars;
use EAG\EasyHydrator\Tests\Fixture\TestUnion;
use Exception;
use Generator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ArrayToValueObjectHydratorTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    /**
     * @dataProvider hydrateArrayCases
     * @param array<mixed> $data
     * @param class-string $className
     */
    public function testHydrateArray(array $data, string $className, object $expected): void
    {
        /** @var ArrayToValueObjectHydrator $arrayToValueObjectHydrator */
        $arrayToValueObjectHydrator = self::getContainer()->get(ArrayToValueObjectHydrator::class);

        $actual = $arrayToValueObjectHydrator->hydrateArray($data, $className);

        self::assertEquals($expected, $actual);

        $actual = $arrayToValueObjectHydrator->hydrateArrays([$data, $data], $className);

        self::assertEquals([$expected, $expected], $actual);
    }

    /**
     * @return Generator<mixed>
     */
    public function hydrateArrayCases(): Generator
    {
        yield 'object' => [
            ['value' => 'test'],
            TestA::class,
            new TestA('test'),
        ];

        yield 'nested' => [
            [
                'valueA' => new TestA('test'),
                'valueB' => new TestB(100),
            ],
            TestAB::class,
            new TestAB(
                valueA: new TestA('test'),
                valueB: new TestB(100),
            ),
        ];

        $stringable = new class() {
            public function __toString(): string
            {
                return 'test';
            }
        };

        yield 'strings' => [
            ['strings' => [$stringable, 'test', 111, 111.111, true, false]],
            TestScalars::class,
            // Bool converted to 1/0
            new TestScalars(strings: ['test', 'test', '111', '111.111', '1', '0']),
        ];

        yield 'integers' => [
            ['integers' => ['111', 111, 111.000, true, false]],
            TestScalars::class,
            new TestScalars(integers: [111, 111, 111, 1, 0]),
        ];

        yield 'floats' => [
            ['floats' => ['111.111', '111.000', 111, 111.111, 111.000, true, false]],
            TestScalars::class,
            // Integer passed as is
            new TestScalars(floats: [111.111, 111.0, 111, 111.111, 111.0, 1.0, 0.0]),
        ];

        yield 'booleans' => [
            ['booleans' => ['111', '111.111', '0', '0.0', 111, 111.111, 0, 0.0, true, false]],
            TestScalars::class,
            new TestScalars(booleans: [true, true, false, false, true, true, false, false, true, false]),
        ];

        yield 'union' => [
            ['value' => ['value' => 'test']],
            TestUnion::class,
            new TestUnion(new TestA('test')),
        ];

        yield 'union keep already set value' => [
            ['value' => new TestB(100)],
            TestUnion::class,
            new TestUnion(new TestB(100)),
        ];

        yield 'datetime' => [
            [
                'value1' => '2020-02-02',
                'value2' => '2020-02-02',
                'value3' => '2020-02-02',
            ],
            TestDateTime::class,
            new TestDateTime(
                DateTime::createFromFormat(DATE_RFC3339_EXTENDED, '2020-02-02T00:00:00.000+0000'),
                DateTime::createFromFormat(DATE_RFC3339_EXTENDED, '2020-02-02T00:00:00.000+0000'),
                DateTimeImmutable::createFromFormat(DATE_RFC3339_EXTENDED, '2020-02-02T00:00:00.000+0000'),
            ),
        ];

        yield 'arrays' => [
            [
                'values' => [
                    ['value' => 'test1'],
                    ['value' => 'test2'],
                ],
                'indexedValues' => [
                    'test_index_1' => ['value' => 'test3'],
                    'test_index_2' => ['value' => 'test4'],
                ],
                'nestedValues' => [
                    [[['value' => 'test5']]],
                    [[['value' => 'test6']]],
                ],
            ],
            TestArray::class,
            new TestArray(
                values: [
                    new TestA('test1'),
                    new TestA('test2'),
                ],
                indexedValues: [
                    'test_index_1' => new TestA('test3'),
                    'test_index_2' => new TestA('test4'),
                ],
                nestedValues: [
                    [[new TestA('test5')]],
                    [[new TestA('test6')]],
                ],
            ),
        ];

        yield 'defaults' => [
            [],
            TestDefaults::class,
            new TestDefaults(
                value1: 'test',
                value2: 100,
                value3: 111.111,
                value4: true,
                value5: null,
                value6: null,
            ),
        ];

        yield 'defaults override' => [
            [
                'value1' => 'test2',
                'value2' => 200,
                'value3' => 222.222,
                'value4' => false,
                'value5' => 'test3',
                'value6' => 'test4',
            ],
            TestDefaults::class,
            new TestDefaults(
                value1: 'test2',
                value2: 200,
                value3: 222.222,
                value4: false,
                value5: 'test3',
                value6: 'test4',
            ),
        ];

        yield 'union with default object value' => [
            [
                'string' => 'A',
                'object' => [
                    'value' => 'B',
                ],
                'arrayOfStrings' => ['C'],
                'arrayOfObjects' => [[
                    'value' => 'D',
                ]],
            ],
            TestNotSet::class,
            new TestNotSet(
                string: 'A',
                object: new TestA('B'),
                arrayOfStrings: ['C'],
                arrayOfObjects: [new TestA('D')],
            ),
        ];

        yield 'union with only default values' => [
            [
            ],
            TestNotSet::class,
            new TestNotSet(
                string: new NotSet(),
                object: new NotSet(),
                arrayOfStrings: new NotSet(),
                arrayOfObjects: new NotSet(),
            ),
        ];
    }

    /**
     * @dataProvider validationCases
     * @param array<mixed> $data
     * @param class-string $className
     */
    public function testHydrateArrayValidation(array $data, string $className, Exception $expectedException): void
    {
        self::expectExceptionObject($expectedException);

        /** @var ArrayToValueObjectHydrator $arrayToValueObjectHydrator */
        $arrayToValueObjectHydrator = self::getContainer()->get(ArrayToValueObjectHydrator::class);

        $arrayToValueObjectHydrator->hydrateArray($data, $className);
    }

    /**
     * @dataProvider validationCases
     * @param array<mixed> $data
     * @param class-string $className
     */
    public function testHydrateArraysValidation(array $data, string $className, Exception $expectedException): void
    {
        self::expectExceptionObject($expectedException);

        /** @var ArrayToValueObjectHydrator $arrayToValueObjectHydrator */
        $arrayToValueObjectHydrator = self::getContainer()->get(ArrayToValueObjectHydrator::class);

        $arrayToValueObjectHydrator->hydrateArrays([$data], $className);
    }

    /**
     * @return Generator<mixed>
     */
    public function validationCases(): Generator
    {
        yield [[], TestA::class, new MissingDataException('Missing data of "$value" parameter for hydrated class "EAG\EasyHydrator\Tests\Fixture\TestA" __construct method.')];
        yield [[], TestNoConstructor::class, new MissingConstructorException('Hydrated class "EAG\EasyHydrator\Tests\Fixture\TestNoConstructor" is missing constructor.')];
    }
}
