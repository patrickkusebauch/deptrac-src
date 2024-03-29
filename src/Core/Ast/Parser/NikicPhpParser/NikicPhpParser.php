<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Core\Ast\Parser\NikicPhpParser;

use PhpParser\Error;
use PhpParser\ErrorHandler\Throwing;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use Qossmic\Deptrac\Contract\Ast\CouldNotParseFileException;
use Qossmic\Deptrac\Core\Ast\AstMap\File\FileReference;
use Qossmic\Deptrac\Core\Ast\AstMap\File\FileReferenceBuilder;
use Qossmic\Deptrac\Core\Ast\Parser\AbstractParser;
use Qossmic\Deptrac\Core\Ast\Parser\Cache\AstFileReferenceCacheInterface;
use Qossmic\Deptrac\Core\Ast\Parser\Extractors\ReferenceExtractorInterface;
use Qossmic\Deptrac\Supportive\File\Exception\CouldNotReadFileException;
use Qossmic\Deptrac\Supportive\File\FileReader;

class NikicPhpParser extends AbstractParser
{
    /**
     * @param ReferenceExtractorInterface<\PhpParser\Node>[] $extractors
     */
    public function __construct(
        private readonly Parser $parser,
        private readonly AstFileReferenceCacheInterface $cache,
        private readonly iterable $extractors
    ) {
        $this->traverser = new NodeTraverser();
        $this->traverser->addVisitor(new NameResolver());
    }

    public function parseFile(string $file): FileReference
    {
        if (null !== $fileReference = $this->cache->get($file)) {
            return $fileReference;
        }

        $fileReferenceBuilder = FileReferenceBuilder::create($file);
        $visitor = new FileReferenceVisitor($fileReferenceBuilder, ...$this->extractors);
        $nodes = $this->loadNodesFromFile($file);
        $this->traverser->addVisitor($visitor);
        $this->traverser->traverse($nodes);
        $this->traverser->removeVisitor($visitor);

        $fileReference = $fileReferenceBuilder->build();
        $this->cache->set($fileReference);

        return $fileReference;
    }

    /**
     * @return array<Node>
     *
     * @throws CouldNotParseFileException
     */
    protected function loadNodesFromFile(string $filepath): array
    {
        try {
            $fileContents = FileReader::read($filepath);
            /** @throws Error */
            $nodes = $this->parser->parse($fileContents, new Throwing());

            /** @var array<Node> $nodes */
            return $nodes;
        } catch (Error|CouldNotReadFileException $e) {
            throw CouldNotParseFileException::because($e->getMessage(), $e);
        }
    }
}
