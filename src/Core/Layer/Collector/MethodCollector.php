<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Core\Layer\Collector;

use Qossmic\Deptrac\Contract\Ast\TokenReferenceInterface;
use Qossmic\Deptrac\Core\Ast\AstMap\ClassLike\ClassLikeReference;
use Qossmic\Deptrac\Core\Ast\Parser\NikicPhpParser\NikicPhpParser;

final class MethodCollector extends RegexCollector
{
    public function __construct(private readonly NikicPhpParser $astParser) {}

    public function satisfy(array $config, TokenReferenceInterface $reference): bool
    {
        if (!$reference instanceof ClassLikeReference) {
            return false;
        }

        $pattern = $this->getValidatedPattern($config);

        $classLike = $this->astParser->getNodeForClassLikeReference($reference);

        if (null === $classLike) {
            return false;
        }

        foreach ($classLike->getMethods() as $classMethod) {
            if (1 === preg_match($pattern, (string) $classMethod->name)) {
                return true;
            }
        }

        return false;
    }

    protected function getPattern(string $config): string
    {
        return '/'.$config.'/i';
    }
}
