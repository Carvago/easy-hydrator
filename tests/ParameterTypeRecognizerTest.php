<?php

declare(strict_types=1);

namespace Symplify\EasyHydrator\Tests;

use ReflectionClass;
use ReflectionMethod;
use Symplify\EasyHydrator\ParameterTypeRecognizer;
use Symplify\EasyHydrator\Tests\Fixture\DocTypeTestObject;
use Symplify\EasyHydrator\Tests\Fixture\Person;
use Symplify\EasyHydrator\Tests\HttpKernel\EasyHydratorTestKernel;
use Symplify\PackageBuilder\Testing\AbstractKernelTestCase;

final class ParameterTypeRecognizerTest extends AbstractKernelTestCase
{
    private ParameterTypeRecognizer $parameterTypeRecognizer;

    protected function setUp(): void
    {
        $this->bootKernel(EasyHydratorTestKernel::class);

        $this->parameterTypeRecognizer = $this->getService(ParameterTypeRecognizer::class);
    }

    public function test(): void
    {
        $reflectionClass = new ReflectionClass(DocTypeTestObject::class);

        /** @var ReflectionMethod $constructorReflectionMethod */
        $constructorReflectionMethod = $reflectionClass->getConstructor();
        $reflectionParameters = $constructorReflectionMethod->getParameters();

        for ($i = 0; $i < 6; ++$i) {
            $actual = $this->parameterTypeRecognizer->getTypeFromDocBlock($reflectionParameters[$i]);
            $this->assertSame('string', $actual, (string) $i);
        }

        for ($i = 6; $i < 12; ++$i) {
            $actual = $this->parameterTypeRecognizer->getTypeFromDocBlock($reflectionParameters[$i]);
            $this->assertSame(Person::class, $actual, (string) $i);
        }

        // 'string' declared before Person, but ReflectionUnionType->getTypes() moves classed to the beginning
        $this->assertSame(Person::class, $this->parameterTypeRecognizer->getType($reflectionParameters[12]));
        $this->assertSame(Person::class, $this->parameterTypeRecognizer->getType($reflectionParameters[13]));
    }
}
