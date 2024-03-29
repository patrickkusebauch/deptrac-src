<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Core\Ast\Parser;

use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\FindingVisitor;
use Qossmic\Deptrac\Contract\Ast\CouldNotParseFileException;
use Qossmic\Deptrac\Core\Ast\AstMap\ClassLike\ClassLikeReference;

abstract class AbstractParser implements ParserInterface
{
    /**
     * @var array<string, ClassLike>
     */
    protected static array $classAstMap = [];

    protected NodeTraverser $traverser;

    /**
     * @throws CouldNotParseFileException
     */
    public function getNodeForClassLikeReference(ClassLikeReference $classReference): ?ClassLike
    {
        $classLikeName = $classReference->getToken()->toString();

        if (isset(self::$classAstMap[$classLikeName])) {
            return self::$classAstMap[$classLikeName];
        }

        $filepath = $classReference->getFilepath();

        if (null === $filepath) {
            return null;
        }

        $visitor = new FindingVisitor(static fn (Node $node): bool => $node instanceof ClassLike);
        $nodes = $this->loadNodesFromFile($filepath);
        $this->traverser->addVisitor($visitor);
        $this->traverser->traverse($nodes);
        $this->traverser->removeVisitor($visitor);

        /** @var ClassLike[] $classLikeNodes */
        $classLikeNodes = $visitor->getFoundNodes();

        foreach ($classLikeNodes as $classLikeNode) {
            if (isset($classLikeNode->namespacedName)) {
                $namespacedName = $classLikeNode->namespacedName;
                $className = $namespacedName->toCodeString();
            } elseif ($classLikeNode->name instanceof Identifier) {
                $className = $classLikeNode->name->toString();
            } else {
                continue;
            }

            self::$classAstMap[$className] = $classLikeNode;
        }

        /** @psalm-var ?ClassLike */
        return self::$classAstMap[$classLikeName] ?? null;
    }

    /**
     * @return array<Node>
     *
     * @throws CouldNotParseFileException
     */
    abstract protected function loadNodesFromFile(string $filepath): array;
}
