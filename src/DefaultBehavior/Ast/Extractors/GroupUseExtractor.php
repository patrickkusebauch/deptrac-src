<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\DefaultBehavior\Ast\Extractors;

use PhpParser\Node;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Use_;
use Qossmic\Deptrac\Contract\Ast\AstMap\ClassLikeToken;
use Qossmic\Deptrac\Contract\Ast\AstMap\DependencyType;
use Qossmic\Deptrac\Contract\Ast\AstMap\ReferenceBuilderInterface;
use Qossmic\Deptrac\Contract\Ast\ReferenceExtractorInterface;
use Qossmic\Deptrac\Contract\Ast\TypeScope;

/**
 * @implements ReferenceExtractorInterface<GroupUse>
 */
final class GroupUseExtractor implements ReferenceExtractorInterface
{
    public function processNode(Node $node, ReferenceBuilderInterface $referenceBuilder, TypeScope $typeScope): void
    {
        foreach ($node->uses as $use) {
            if (Use_::TYPE_NORMAL === $use->type) {
                $classLikeName = $node->prefix->toString().'\\'.$use->name->toString();
                $referenceBuilder->dependency(ClassLikeToken::fromFQCN($classLikeName), $use->name->getLine(), DependencyType::USE);
            }
        }
    }

    public function getNodeType(): string
    {
        return GroupUse::class;
    }
}