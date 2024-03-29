<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Core\Ast\Parser;

use Qossmic\Deptrac\Core\Ast\AstMap\File\FileReference;
use Qossmic\Deptrac\Core\Ast\Parser\NikicPhpParser\NikicPhpParser;
use Qossmic\Deptrac\Core\Ast\Parser\PhpStanParser\PhpStanParser;

final class DelegatingParser extends AbstractParser
{
    /**
     * @param  array{phpstan_parser: bool, ...}  $featureFlags
     */
    public function __construct(
        private readonly array $featureFlags,
        private readonly NikicPhpParser $nikicPhpParser,
        private readonly PhpStanParser $phpStanParser,
    ) {}

    protected function loadNodesFromFile(string $filepath): array
    {
        if ($this->featureFlags['phpstan_parser']) {
            return $this->phpStanParser->loadNodesFromFile($filepath);
        }

        return $this->nikicPhpParser->loadNodesFromFile($filepath);
    }

    public function parseFile(string $file): FileReference
    {
        if ($this->featureFlags['phpstan_parser']) {
            return $this->phpStanParser->parseFile($file);
        }

        return $this->nikicPhpParser->parseFile($file);
    }
}
