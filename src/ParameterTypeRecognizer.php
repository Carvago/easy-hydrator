<?php

declare(strict_types=1);

namespace Symplify\EasyHydrator;

use Nette\Utils\Reflection;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;

/**
 * @see \Symplify\EasyHydrator\Tests\ParameterTypeRecognizerTest
 */
final class ParameterTypeRecognizer
{
    public function isArray(ReflectionParameter $reflectionParameter): bool
    {
        $type = $this->getTypeFromTypeHint($reflectionParameter);

        if ($type === 'array') {
            return true;
        }

        $paramTypeNode = $this->getParamTypeNode($reflectionParameter);

        if ($paramTypeNode instanceof UnionTypeNode) {
            $paramTypeNode = $paramTypeNode->types[0];
        }

        return $paramTypeNode instanceof ArrayTypeNode || ($paramTypeNode instanceof GenericTypeNode && 'array' === $paramTypeNode->type->name);
    }

    public function getType(ReflectionParameter $reflectionParameter): ?string
    {
        $type = $this->getTypeFromTypeHint($reflectionParameter);
        if ($type) {
            return $type;
        }

        return $this->getTypeFromDocBlock($reflectionParameter);
    }

    public function isParameterOfType(ReflectionParameter $reflectionParameter, string $type): bool
    {
        $hintType = $this->getTypeFromTypeHint($reflectionParameter);
        if (null !== $hintType && ($hintType === $type || is_a($hintType, $type, true))) {
            return true;
        }

        $docBlockType = $this->getTypeFromDocBlock($reflectionParameter);
        if (null !== $docBlockType && ($docBlockType === $type || is_a($docBlockType, $type, true))) {
            return true;
        }
        if ('array' === $hintType && null !== $docBlockType && ($docBlockType === $type || is_a($docBlockType, $type, true))) {
            return true;
        }

        return false;
    }

    public function getTypeFromDocBlock(ReflectionParameter $reflectionParameter): ?string
    {
        $paramTypeNode = $this->getParamTypeNode($reflectionParameter);
        $contextReflectionClass = $reflectionParameter->getDeclaringClass();

        return $this->getTypeFromParamTypeNode($paramTypeNode, $contextReflectionClass);
    }

    private function getTypeFromParamTypeNode(?TypeNode $paramTypeNode, ?ReflectionClass $contextReflectionClass): ?string
    {
        if (null === $paramTypeNode) {
            return null;
        }

        if ($paramTypeNode instanceof UnionTypeNode) {
            return $this->getTypeFromParamTypeNode($paramTypeNode->types[0], $contextReflectionClass);
        }

        if ($paramTypeNode instanceof ArrayTypeNode) {
            return $this->getTypeFromParamTypeNode($paramTypeNode->type, $contextReflectionClass);
        }

        if ($paramTypeNode instanceof GenericTypeNode) {
            // Take last declared type for some reason
            return $this->getTypeFromParamTypeNode($paramTypeNode->genericTypes[count($paramTypeNode->genericTypes) - 1], $contextReflectionClass);
        }

        if ($paramTypeNode instanceof IdentifierTypeNode) {
            $typeNodeName = (string) $paramTypeNode;

            if (null !== $contextReflectionClass && !$contextReflectionClass->isAnonymous()) {
                return Reflection::expandClassName($typeNodeName, $contextReflectionClass);
            }

            // In case of FQCN in anonymous classes
            return class_exists($typeNodeName) ? ltrim($typeNodeName, '\\') : $typeNodeName;
        }

        return null;
    }

    public function getArrayLevels(ReflectionParameter $reflectionParameter): int
    {
        $paramTypeNode = $this->getParamTypeNode($reflectionParameter);
        if (null === $paramTypeNode) {
            return 0;
        }

        $callback = function (TypeNode $typeNode, int &$level) use ($reflectionParameter, &$callback): void {
            if ($typeNode instanceof UnionTypeNode) {
                $callback($typeNode->types[0], $level);
            }
            if ($typeNode instanceof ArrayTypeNode) {
                $level++;
                $callback($typeNode->type, $level);
            }
            if ($typeNode instanceof GenericTypeNode && 'array' === $typeNode->type->name) {
                $level++;
                $callback($typeNode->genericTypes[0], $level);
            }
        };

        $level = 0;
        $callback($paramTypeNode, $level);

        return $level;
    }

    private function getTypeFromTypeHint(ReflectionParameter $reflectionParameter): ?string
    {
        return match (true) {
            $reflectionParameter->getType() instanceof ReflectionUnionType => $reflectionParameter->getType()->getTypes()[0]->getName(),
            $reflectionParameter->getType() instanceof ReflectionNamedType => $reflectionParameter->getType()->getName(),
            default => null,
        };
    }

    private function getParamTypeNode(ReflectionParameter $reflectionParameter): ?TypeNode
    {
        $functionReflection = $reflectionParameter->getDeclaringFunction();
        $docComment = $functionReflection->getDocComment();

        if ($docComment === false) {
            return null;
        }

        $phpDocParser = new PhpDocParser(new TypeParser(new ConstExprParser()), new ConstExprParser());
        $lexer = new Lexer();

        $tokens = $lexer->tokenize($docComment);
        $tokenIterator = new TokenIterator($tokens);

        $docNode = $phpDocParser->parse($tokenIterator);

        foreach ($docNode->getParamTagValues() as $paramTagValueNode) {
            if ($paramTagValueNode->parameterName === '$' . $reflectionParameter->getName()) {
                return $paramTagValueNode->type;
            }
        }

        return null;
    }
}
