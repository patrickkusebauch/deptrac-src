<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\DefaultBehavior\Dependency;

use Qossmic\Deptrac\Contract\Ast\AstMap\AstMapInterface;
use Qossmic\Deptrac\Contract\Ast\AstMap\DependencyType;
use Qossmic\Deptrac\Contract\Dependency\DependencyEmitterInterface;
use Qossmic\Deptrac\Contract\Dependency\DependencyListInterface;
use Qossmic\Deptrac\DefaultBehavior\Dependency\Helpers\Dependency;

final class FunctionDependencyEmitter implements DependencyEmitterInterface
{
    public function getName(): string
    {
        return 'FunctionDependencyEmitter';
    }

    public function applyDependencies(AstMapInterface $astMap, DependencyListInterface $dependencyList): void
    {
        foreach ($astMap->getFileReferences() as $astFileReference) {
            foreach ($astFileReference->functionReferences as $astFunctionReference) {
                foreach ($astFunctionReference->dependencies as $dependency) {
                    if (DependencyType::SUPERGLOBAL_VARIABLE === $dependency->context->dependencyType) {
                        continue;
                    }

                    if (DependencyType::UNRESOLVED_FUNCTION_CALL === $dependency->context->dependencyType) {
                        continue;
                    }

                    $dependencyList->addDependency(
                        new Dependency(
                            $astFunctionReference->getToken(),
                            $dependency->token,
                            $dependency->context,
                        )
                    );
                }
            }
        }
    }
}
