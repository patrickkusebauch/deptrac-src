<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\DefaultBehavior\Ast\Extractors;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use Qossmic\Deptrac\Contract\Ast\AstMap\ClassLikeToken;
use Qossmic\Deptrac\Contract\Ast\AstMap\DependencyType;
use Qossmic\Deptrac\Contract\Ast\AstMap\ReferenceBuilderInterface;
use Qossmic\Deptrac\Contract\Ast\ReferenceExtractorInterface;
use Qossmic\Deptrac\Contract\Ast\TypeScope;

/**
 * @implements ReferenceExtractorInterface<Class_>
 */
final class AnonymousClassExtractor implements ReferenceExtractorInterface
{
    public function processNode(Node $node, ReferenceBuilderInterface $referenceBuilder, TypeScope $typeScope): void
    {
        if (null !== $node->name) {
            return;
        }

        if ($node->extends instanceof Name) {
            $referenceBuilder->dependency(ClassLikeToken::fromFQCN($node->extends->toCodeString()), $node->extends->getLine(), DependencyType::ANONYMOUS_CLASS_EXTENDS);
        }

        foreach ($node->implements as $implement) {
            $referenceBuilder->dependency(ClassLikeToken::fromFQCN($implement->toCodeString()), $implement->getLine(), DependencyType::ANONYMOUS_CLASS_IMPLEMENTS);
        }

        foreach ($node->getTraitUses() as $traitUse) {
            foreach ($traitUse->traits as $trait) {
                $referenceBuilder->dependency(ClassLikeToken::fromFQCN($trait->toCodeString()), $trait->getLine(), DependencyType::ANONYMOUS_CLASS_TRAIT);
            }
        }
    }

    public function getNodeType(): string
    {
        return Class_::class;
    }
}