<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Core\Layer\Collector;

use Qossmic\Deptrac\Contract\Ast\TokenReferenceInterface;
use Symfony\Component\Filesystem\Path;

final class DirectoryCollector extends RegexCollector
{
    public function satisfy(array $config, TokenReferenceInterface $reference): bool
    {
        $filepath = $reference->getFilepath();

        if (null === $filepath) {
            return false;
        }

        $validatedPattern = $this->getValidatedPattern($config);
        $normalizedPath = Path::normalize($filepath);

        return 1 === preg_match($validatedPattern, $normalizedPath);
    }

    protected function getPattern(string $config): string
    {
        return '#'.$config.'#i';
    }
}
