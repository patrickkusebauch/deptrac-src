<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\DefaultBehavior\Dependency;

use Qossmic\Deptrac\Contract\Ast\AstMap\AstMapInterface;
use Qossmic\Deptrac\Contract\Ast\AstMap\ClassLikeReference;
use Qossmic\Deptrac\Contract\Ast\AstMap\DependencyType;
use Qossmic\Deptrac\Contract\Ast\AstMap\FileReference;
use Qossmic\Deptrac\Contract\Ast\AstMap\FunctionReference;
use Qossmic\Deptrac\Contract\Ast\AstMap\FunctionToken;
use Qossmic\Deptrac\Contract\Dependency\DependencyEmitterInterface;
use Qossmic\Deptrac\Contract\Dependency\DependencyListInterface;
use Qossmic\Deptrac\DefaultBehavior\Dependency\Helpers\Dependency;

final class FunctionCallDependencyEmitter implements DependencyEmitterInterface
{
    public function getName(): string
    {
        return 'FunctionCallDependencyEmitter';
    }

    public function applyDependencies(AstMapInterface $astMap, DependencyListInterface $dependencyList): void
    {
        $this->createDependenciesForReferences($astMap->getClassLikeReferences(), $astMap, $dependencyList);
        $this->createDependenciesForReferences($astMap->getFunctionReferences(), $astMap, $dependencyList);
        $this->createDependenciesForReferences($astMap->getFileReferences(), $astMap, $dependencyList);
    }

    /**
     * @param array<FunctionReference|ClassLikeReference|FileReference> $references
     */
    private function createDependenciesForReferences(array $references, AstMapInterface $astMap, DependencyListInterface $dependencyList): void
    {
        foreach ($references as $reference) {
            foreach ($reference->dependencies as $dependency) {
                if (DependencyType::UNRESOLVED_FUNCTION_CALL !== $dependency->context->dependencyType) {
                    continue;
                }

                $token = $dependency->token;
                assert($token instanceof FunctionToken);
                if (null === $astMap->getFunctionReferenceForToken($token)) {
                    continue;
                }

                $dependencyList->addDependency(
                    new Dependency(
                        $reference->getToken(), $dependency->token, $dependency->context
                    )
                );
            }
        }
    }
}
