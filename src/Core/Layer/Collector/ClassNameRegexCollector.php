<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Core\Layer\Collector;

use Qossmic\Deptrac\Contract\Ast\TokenReferenceInterface;
use Qossmic\Deptrac\Core\Ast\AstMap\ClassLike\ClassLikeReference;

final class ClassNameRegexCollector extends RegexCollector
{
    public function satisfy(array $config, TokenReferenceInterface $reference): bool
    {
        if (!$reference instanceof ClassLikeReference) {
            return false;
        }

        return $reference->getToken()->match($this->getValidatedPattern($config));
    }

    protected function getPattern(string $config): string
    {
        return $config;
    }
}
