<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Contract\Ast;

use PhpParser\Node;
use Qossmic\Deptrac\Contract\Ast\AstMap\ReferenceBuilderInterface;

/**
 * Interface for defining references between tokens. You can catch any Nikic PHP
 * parser node and define a dependency based on this node on another token.
 *
 * @template T of Node
 */
interface ReferenceExtractorInterface
{
    /**
     * @return class-string<T>
     */
    public function getNodeType(): string;

    /**
     * @param T $node
     */
    public function processNode(Node $node, ReferenceBuilderInterface $referenceBuilder, TypeScope $typeScope): void;
}