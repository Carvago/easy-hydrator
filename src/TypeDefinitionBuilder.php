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
use RuntimeException;

final class TypeDefinitionBuilder
{
    /**
     * @var array<string|class-string>
     */
    private array $types = [];
    private ?self $innerTypeDefinitionBuilder = null;

    /**
     * @param ReflectionParameter $reflectionParameter
     * @param TypeNode|null $typeNode
     */
    private function __construct(
        private ReflectionParameter $reflectionParameter,
        private ?TypeNode $typeNode = null,
    ) {
    }

    public static function create(ReflectionParameter $reflectionParameter): self
    {
        return new self($reflectionParameter);
    }

    public function build(): TypeDefinition
    {
        // ReflectionParameter is processed only once
        if (null === $this->typeNode) {
            $this->types = self::getTypeHintTypes($this->reflectionParameter);

            $typeNode = self::getParamTypeNode($this->reflectionParameter);
            $this->typeNode = $typeNode;
        }

        if (null !== $this->typeNode) {
            $innerType = self::parseDocBlockType($this->typeNode, $this->types, $this->reflectionParameter->getDeclaringClass());
            if (null !== $innerType) {
                $this->innerTypeDefinitionBuilder = new self($this->reflectionParameter, $innerType);
            }
        }

        // Integers also could be passed as floats
        if (in_array(TypeDefinition::FLOAT, $this->types) && !in_array(TypeDefinition::INT, $this->types)) {
            $this->types[] = TypeDefinition::INT;
        }

        $this->types = array_values(array_unique($this->types));

        return new TypeDefinition($this->types, $this->innerTypeDefinitionBuilder?->build());
    }

    private static function getTypeHintTypes(ReflectionParameter $reflectionParameter): array
    {
        return match (true) {
            $reflectionParameter->getType() instanceof ReflectionUnionType => array_map(
                callback: fn (ReflectionNamedType $type) => $type->getName(),
                array: $reflectionParameter->getType()->getTypes(),
            ),
            $reflectionParameter->getType() instanceof ReflectionNamedType => $reflectionParameter->getType()->allowsNull() ?
                [$reflectionParameter->getType()->getName(), TypeDefinition::NULL] :
                [$reflectionParameter->getType()->getName()],
            default => [],
        };
    }

    /**
     * Probably not the best way to do this
     * But here it's needed to separate parsed types and nested TypeNode[] for further processing
     *
     * @param array<string|class-string>|null $types
     */
    private static function parseDocBlockType(TypeNode $typeNode, ?array &$types, ReflectionClass $contextReflectionClass): ?TypeNode
    {
        $types ??= [];
        $innerType = null;

        if ($typeNode instanceof UnionTypeNode) {
            foreach ($typeNode->types as $i => $unionType) {
                $unionInnerType = self::parseDocBlockType($unionType, $types, $contextReflectionClass);
                $innerType ??= $unionInnerType;
            }
        }

        if ($typeNode instanceof ArrayTypeNode) {
            $types[] = TypeDefinition::ARRAY;
            $innerType ??= $typeNode->type;
        }

        if ($typeNode instanceof GenericTypeNode) {
            if ($typeNode->type->name !== TypeDefinition::ARRAY) {
                throw new RuntimeException('Generics syntax some<of> is supported only for arrays, given: ' . $typeNode->type->name);
            }
            $types[] = TypeDefinition::ARRAY;

            // Skip key in indexed arrays
            $innerType ??= $typeNode->genericTypes[1] ?? $typeNode->genericTypes[0];
        }

        if ($typeNode instanceof IdentifierTypeNode) {
            if (!$contextReflectionClass->isAnonymous()) {
                $types[] = Reflection::expandClassName($typeNode->name, $contextReflectionClass);
            }

            // In case of FQCN in anonymous classes
            if (class_exists($typeNode->name)) {
                $types[] = ltrim($typeNode->name, '\\');
            }
        }

        return $innerType;
    }

    private static function getParamTypeNode(ReflectionParameter $reflectionParameter): ?TypeNode
    {
        $docComment = $reflectionParameter->getDeclaringFunction()->getDocComment();
        if (false === $docComment) {
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
