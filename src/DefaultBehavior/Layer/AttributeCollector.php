<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\DefaultBehavior\Layer;

use Qossmic\Deptrac\Contract\Ast\AstMap\ClassLikeReference;
use Qossmic\Deptrac\Contract\Ast\AstMap\DependencyType;
use Qossmic\Deptrac\Contract\Ast\AstMap\FileReference;
use Qossmic\Deptrac\Contract\Ast\AstMap\FunctionReference;
use Qossmic\Deptrac\Contract\Ast\AstMap\TokenReferenceInterface;
use Qossmic\Deptrac\Contract\Layer\CollectorInterface;
use Qossmic\Deptrac\Contract\Layer\InvalidCollectorDefinitionException;

use function str_contains;

final class AttributeCollector implements CollectorInterface
{
    public function satisfy(array $config, TokenReferenceInterface $reference): bool
    {
        if (!$reference instanceof FileReference
            && !$reference instanceof ClassLikeReference
            && !$reference instanceof FunctionReference
        ) {
            return false;
        }

        $match = $this->getSearchedSubstring($config);

        foreach ($reference->dependencies as $dependency) {
            if (DependencyType::ATTRIBUTE !== $dependency->context->dependencyType) {
                continue;
            }

            $usedAttribute = $dependency->token->toString();

            if (str_contains($usedAttribute, $match)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, bool|string|array<string, string>> $config
     *
     * @throws InvalidCollectorDefinitionException
     */
    private function getSearchedSubstring(array $config): string
    {
        if (!isset($config['value']) || !is_string($config['value'])) {
            throw InvalidCollectorDefinitionException::invalidCollectorConfiguration('AttributeCollector needs the attribute name as a string.');
        }

        return $config['value'];
    }
}
