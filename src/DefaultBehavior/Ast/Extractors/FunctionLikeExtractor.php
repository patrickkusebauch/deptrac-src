<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\DefaultBehavior\Ast\Extractors;

use PhpParser\Node;
use Qossmic\Deptrac\Contract\Ast\AstMap\ClassLikeToken;
use Qossmic\Deptrac\Contract\Ast\AstMap\DependencyType;
use Qossmic\Deptrac\Contract\Ast\AstMap\ReferenceBuilderInterface;
use Qossmic\Deptrac\Contract\Ast\ReferenceExtractorInterface;
use Qossmic\Deptrac\Contract\Ast\TypeResolverInterface;
use Qossmic\Deptrac\Contract\Ast\TypeScope;

/**
 * @implements ReferenceExtractorInterface<Node\FunctionLike>
 */
final class FunctionLikeExtractor implements ReferenceExtractorInterface
{
    public function __construct(
        private readonly TypeResolverInterface $typeResolver,
    ) {}

    public function processNode(Node $node, ReferenceBuilderInterface $referenceBuilder, TypeScope $typeScope): void
    {
        foreach ($node->getAttrGroups() as $attrGroup) {
            foreach ($attrGroup->attrs as $attribute) {
                foreach ($this->typeResolver->resolvePHPParserTypes($typeScope, $attribute->name) as $classLikeName) {
                    $referenceBuilder->dependency(ClassLikeToken::fromFQCN($classLikeName), $attribute->getLine(), DependencyType::ATTRIBUTE);
                }
            }
        }
        foreach ($node->getParams() as $param) {
            if (null !== $param->type) {
                foreach ($this->typeResolver->resolvePHPParserTypes($typeScope, $param->type) as $classLikeName) {
                    $referenceBuilder->dependency(ClassLikeToken::fromFQCN($classLikeName), $param->type->getLine(), DependencyType::PARAMETER);
                }
            }
        }
        $returnType = $node->getReturnType();
        if (null !== $returnType) {
            foreach ($this->typeResolver->resolvePHPParserTypes($typeScope, $returnType) as $classLikeName) {
                $referenceBuilder->dependency(ClassLikeToken::fromFQCN($classLikeName), $returnType->getLine(), DependencyType::RETURN_TYPE);
            }
        }
    }

    public function getNodeType(): string
    {
        return Node\FunctionLike::class;
    }
}