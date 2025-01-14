<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Core\Layer\Collector;

use Qossmic\Deptrac\Contract\Ast\AstMap\ClassLikeReference;
use Qossmic\Deptrac\Contract\Ast\AstMap\TokenReferenceInterface;
use Qossmic\Deptrac\Contract\Ast\ParserInterface;
use Qossmic\Deptrac\Contract\Layer\InvalidCollectorDefinitionException;

final class MethodCollector extends RegexCollector
{
    public function __construct(private readonly ParserInterface $astParser) {}

    public function satisfy(array $config, TokenReferenceInterface $reference): bool
    {
        if (!$reference instanceof ClassLikeReference) {
            return false;
        }

        $pattern = $this->getValidatedPattern($config);

        $classMethods = $this->astParser->getMethodNamesForClassLikeReference($reference);

        foreach ($classMethods as $classMethod) {
            if (1 === preg_match($pattern, $classMethod)) {
                return true;
            }
        }

        return false;
    }

    protected function getPattern(array $config): string
    {
        if (!isset($config['value']) || !is_string($config['value'])) {
            throw InvalidCollectorDefinitionException::invalidCollectorConfiguration('MethodCollector needs the name configuration.');
        }

        return '/'.$config['value'].'/i';
    }
}
