<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\DefaultBehavior\Dependency;

use Qossmic\Deptrac\Contract\Ast\AstMap\AstMapInterface;
use Qossmic\Deptrac\Contract\Ast\AstMap\DependencyToken;
use Qossmic\Deptrac\Contract\Ast\AstMap\DependencyType;
use Qossmic\Deptrac\Contract\Dependency\DependencyEmitterInterface;
use Qossmic\Deptrac\Contract\Dependency\DependencyListInterface;
use Qossmic\Deptrac\DefaultBehavior\Dependency\Helpers\Dependency;
use Qossmic\Deptrac\DefaultBehavior\Dependency\Helpers\FQDNIndexNode;

use function array_map;
use function array_merge;
use function explode;

final class UsesDependencyEmitter implements DependencyEmitterInterface
{
    public function getName(): string
    {
        return 'UsesDependencyEmitter';
    }

    public function applyDependencies(AstMapInterface $astMap, DependencyListInterface $dependencyList): void
    {
        $references = array_merge($astMap->getClassLikeReferences(), $astMap->getFunctionReferences());
        $referencesFQDN = array_map(
            static fn ($ref): string => $ref->getToken()->toString(),
            $references
        );

        $FQDNIndex = new FQDNIndexNode();
        foreach ($referencesFQDN as $reference) {
            $path = explode('\\', $reference);
            $FQDNIndex->setNestedNode($path);
        }

        foreach ($astMap->getFileReferences() as $fileReference) {
            foreach ($fileReference->classLikeReferences as $astClassReference) {
                foreach ($fileReference->dependencies as $emittedDependency) {
                    if (DependencyType::USE === $emittedDependency->context->dependencyType
                        && $this->isFQDN($emittedDependency, $FQDNIndex)
                    ) {
                        $dependencyList->addDependency(
                            new Dependency(
                                $astClassReference->getToken(),
                                $emittedDependency->token,
                                $emittedDependency->context,
                            )
                        );
                    }
                }
            }
        }
    }

    private function isFQDN(DependencyToken $dependency, FQDNIndexNode $FQDNIndex): bool
    {
        $dependencyFQDN = $dependency->token->toString();
        $path = explode('\\', $dependencyFQDN);
        $value = $FQDNIndex->getNestedNode($path);
        if (null === $value) {
            return true;
        }

        return $value->isFQDN();
    }
}
