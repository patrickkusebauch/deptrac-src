<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Core\Layer\Collector;

use Qossmic\Deptrac\Contract\Ast\TokenReferenceInterface;
use Qossmic\Deptrac\Core\Ast\AstMap\ClassLike\ClassLikeReference;
use Qossmic\Deptrac\Core\Ast\AstMap\ClassLike\ClassLikeType;

abstract class AbstractTypeCollector extends RegexCollector
{
    abstract protected function getType(): ClassLikeType;

    public function satisfy(array $config, TokenReferenceInterface $reference): bool
    {
        if (!$reference instanceof ClassLikeReference) {
            return false;
        }

        $isClassLike = ClassLikeType::TYPE_CLASSLIKE === $this->getType();
        $isSameType = $reference->type === $this->getType();

        return ($isClassLike || $isSameType) && $reference->getToken()->match($this->getValidatedPattern($config));
    }

    protected function getPattern(string $config): string
    {
        return '/'.$config.'/i';
    }
}
