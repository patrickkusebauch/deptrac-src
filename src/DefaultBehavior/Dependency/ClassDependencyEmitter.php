<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\DefaultBehavior\Dependency;

use Qossmic\Deptrac\Contract\Ast\AstMap\AstMapInterface;
use Qossmic\Deptrac\Contract\Ast\AstMap\DependencyContext;
use Qossmic\Deptrac\Contract\Ast\AstMap\DependencyType;
use Qossmic\Deptrac\Contract\Dependency\DependencyEmitterInterface;
use Qossmic\Deptrac\Contract\Dependency\DependencyListInterface;
use Qossmic\Deptrac\DefaultBehavior\Dependency\Helpers\Dependency;

final class ClassDependencyEmitter implements DependencyEmitterInterface
{
    public function getName(): string
    {
        return 'ClassDependencyEmitter';
    }

    public function applyDependencies(AstMapInterface $astMap, DependencyListInterface $dependencyList): void
    {
        foreach ($astMap->getClassLikeReferences() as $classReference) {
            $classLikeName = $classReference->getToken();

            foreach ($classReference->dependencies as $dependency) {
                if (DependencyType::SUPERGLOBAL_VARIABLE === $dependency->context->dependencyType) {
                    continue;
                }
                if (DependencyType::UNRESOLVED_FUNCTION_CALL === $dependency->context->dependencyType) {
                    continue;
                }

                $dependencyList->addDependency(
                    new Dependency(
                        $classLikeName,
                        $dependency->token,
                        $dependency->context,
                    )
                );
            }

            foreach ($astMap->getClassInherits($classLikeName) as $inherit) {
                $dependencyList->addDependency(
                    new Dependency(
                        $classLikeName,
                        $inherit->classLikeName,
                        new DependencyContext($inherit->fileOccurrence, DependencyType::INHERIT),
                    )
                );
            }
        }
    }
}