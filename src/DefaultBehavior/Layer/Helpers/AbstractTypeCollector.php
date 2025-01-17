<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\DefaultBehavior\Layer\Helpers;

use Qossmic\Deptrac\Contract\Ast\AstMap\ClassLikeReference;
use Qossmic\Deptrac\Contract\Ast\AstMap\ClassLikeType;
use Qossmic\Deptrac\Contract\Ast\AstMap\TokenReferenceInterface;
use Qossmic\Deptrac\Contract\Layer\InvalidCollectorDefinitionException;

use function is_string;

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

    protected function getPattern(array $config): string
    {
        if (!isset($config['value']) || !is_string($config['value'])) {
            throw InvalidCollectorDefinitionException::invalidCollectorConfiguration(sprintf('Collector "%s" needs the regex configuration.', $this->getType()->toString()));
        }

        return '/'.$config['value'].'/i';
    }
}
