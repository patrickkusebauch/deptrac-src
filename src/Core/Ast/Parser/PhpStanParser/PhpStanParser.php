<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Core\Ast\Parser\PhpStanParser;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PHPStan\Parser\Parser;
use PHPStan\Parser\ParserErrorsException;
use Qossmic\Deptrac\Contract\Ast\CouldNotParseFileException;
use Qossmic\Deptrac\Core\Ast\AstMap\File\FileReference;
use Qossmic\Deptrac\Core\Ast\AstMap\File\FileReferenceBuilder;
use Qossmic\Deptrac\Core\Ast\Parser\AbstractParser;
use Qossmic\Deptrac\Core\Ast\Parser\Cache\AstFileReferenceCacheInterface;
use Qossmic\Deptrac\Core\Ast\Parser\Extractors\ReferenceExtractorInterface;

class PhpStanParser extends AbstractParser
{
    private Parser $parser;

    /**
     * @param ReferenceExtractorInterface<\PhpParser\Node>[] $extractors
     */
    public function __construct(
        private readonly PhpStanContainerDecorator $phpStanContainer,
        private readonly AstFileReferenceCacheInterface $cache,
        private readonly iterable $extractors
    ) {
        $this->traverser = new NodeTraverser();
        $this->parser = $this->phpStanContainer->createPHPStanParser();
    }

    public function parseFile(string $file): FileReference
    {
        if (null !== $fileReference = $this->cache->get($file)) {
            return $fileReference;
        }

        $scopeFactory = $this->phpStanContainer->createScopeFactory();
        $reflectionProvider = $this->phpStanContainer->createReflectionProvider();

        $fileReferenceBuilder = FileReferenceBuilder::create($file);
        $visitor = new FileReferenceVisitor($fileReferenceBuilder, $scopeFactory, $reflectionProvider, $file, ...$this->extractors);
        $nodes = $this->loadNodesFromFile($file);
        $this->traverser->addVisitor($visitor);
        $this->traverser->traverse($nodes);
        $this->traverser->removeVisitor($visitor);

        return $fileReferenceBuilder->build();
    }

    protected function loadNodesFromFile(string $filepath): array
    {
        try {
            $nodes = $this->parser->parseFile($filepath);

            /** @var array<Node> $nodes */
            return $nodes;
        } catch (ParserErrorsException $exception) {
            throw CouldNotParseFileException::because($exception->getMessage(), $exception);
        }
    }
}
