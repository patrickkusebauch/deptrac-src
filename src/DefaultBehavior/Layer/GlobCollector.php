<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\DefaultBehavior\Layer;

use Qossmic\Deptrac\Contract\Ast\AstMap\TokenReferenceInterface;
use Qossmic\Deptrac\Contract\Layer\InvalidCollectorDefinitionException;
use Qossmic\Deptrac\DefaultBehavior\Layer\Helpers\RegexCollector;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Glob;

final class GlobCollector extends RegexCollector
{
    private readonly string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = Path::normalize($basePath);
    }

    public function satisfy(array $config, TokenReferenceInterface $reference): bool
    {
        $filepath = $reference->getFilepath();

        if (null === $filepath) {
            return false;
        }

        $validatedPattern = $this->getValidatedPattern($config);
        $normalizedPath = Path::normalize($filepath);
        /** @throws void */
        $relativeFilePath = Path::makeRelative($normalizedPath, $this->basePath);

        return 1 === preg_match($validatedPattern, $relativeFilePath);
    }

    protected function getPattern(array $config): string
    {
        if (!isset($config['value']) || !is_string($config['value'])) {
            throw InvalidCollectorDefinitionException::invalidCollectorConfiguration('GlobCollector needs the glob pattern configuration.');
        }

        return Glob::toRegex($config['value']);
    }
}