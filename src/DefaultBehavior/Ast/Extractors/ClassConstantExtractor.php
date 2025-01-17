<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\DefaultBehavior\Ast\Extractors;

use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Name;
use Qossmic\Deptrac\Contract\Ast\AstMap\ClassLikeToken;
use Qossmic\Deptrac\Contract\Ast\AstMap\DependencyType;
use Qossmic\Deptrac\Contract\Ast\AstMap\ReferenceBuilderInterface;
use Qossmic\Deptrac\Contract\Ast\ReferenceExtractorInterface;
use Qossmic\Deptrac\Contract\Ast\TypeScope;

/**
 * @implements ReferenceExtractorInterface<ClassConstFetch>
 */
final class ClassConstantExtractor implements ReferenceExtractorInterface
{
    public function processNode(Node $node, ReferenceBuilderInterface $referenceBuilder, TypeScope $typeScope): void
    {
        if (!$node->class instanceof Name || $node->class->isSpecialClassName()) {
            return;
        }

        $referenceBuilder->dependency(ClassLikeToken::fromFQCN($node->class->toCodeString()), $node->class->getLine(), DependencyType::CONST);
    }

    public function getNodeType(): string
    {
        return ClassConstFetch::class;
    }
}
