<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\DefaultBehavior\Dependency;

use Qossmic\Deptrac\Contract\Ast\AstMap\AstMapInterface;
use Qossmic\Deptrac\Contract\Ast\AstMap\DependencyType;
use Qossmic\Deptrac\Contract\Dependency\DependencyEmitterInterface;
use Qossmic\Deptrac\Contract\Dependency\DependencyListInterface;
use Qossmic\Deptrac\DefaultBehavior\Dependency\Helpers\Dependency;

final class FileDependencyEmitter implements DependencyEmitterInterface
{
    public function getName(): string
    {
        return 'FileDependencyEmitter';
    }

    public function applyDependencies(AstMapInterface $astMap, DependencyListInterface $dependencyList): void
    {
        foreach ($astMap->getFileReferences() as $fileReference) {
            foreach ($fileReference->dependencies as $dependency) {
                if (DependencyType::USE === $dependency->context->dependencyType) {
                    continue;
                }

                if (DependencyType::UNRESOLVED_FUNCTION_CALL === $dependency->context->dependencyType) {
                    continue;
                }

                $dependencyList->addDependency(
                    new Dependency(
                        $fileReference->getToken(),
                        $dependency->token,
                        $dependency->context,
                    )
                );
            }
        }
    }
}
