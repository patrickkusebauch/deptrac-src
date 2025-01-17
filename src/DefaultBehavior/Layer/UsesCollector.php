<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\DefaultBehavior\Layer;

use Qossmic\Deptrac\Contract\Ast\AstException;
use Qossmic\Deptrac\Contract\Ast\AstMap\AstInheritType;
use Qossmic\Deptrac\Contract\Ast\AstMap\AstMapInterface;
use Qossmic\Deptrac\Contract\Ast\AstMap\ClassLikeReference;
use Qossmic\Deptrac\Contract\Ast\AstMap\ClassLikeToken;
use Qossmic\Deptrac\Contract\Ast\AstMap\TokenReferenceInterface;
use Qossmic\Deptrac\Contract\Ast\AstMapExtractorInterface;
use Qossmic\Deptrac\Contract\Ast\CouldNotParseFileException;
use Qossmic\Deptrac\Contract\Layer\CollectorInterface;
use Qossmic\Deptrac\Contract\Layer\InvalidCollectorDefinitionException;

final class UsesCollector implements CollectorInterface
{
    private AstMapInterface $astMap;

    public function __construct(private readonly AstMapExtractorInterface $astMapExtractor) {}

    public function satisfy(array $config, TokenReferenceInterface $reference): bool
    {
        if (!$reference instanceof ClassLikeReference) {
            return false;
        }

        $traitName = $this->getTraitName($config);

        try {
            $this->astMap ??= $this->astMapExtractor->extract();
        } catch (AstException $exception) {
            throw CouldNotParseFileException::because('Could not build Ast map', $exception);
        }
        foreach ($this->astMap->getClassInherits($reference->getToken()) as $inherit) {
            if (AstInheritType::USES === $inherit->type && $inherit->classLikeName->equals($traitName)) {
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
    private function getTraitName(array $config): ClassLikeToken
    {
        if (!isset($config['value']) || !is_string($config['value'])) {
            throw InvalidCollectorDefinitionException::invalidCollectorConfiguration('UsesCollector needs the trait name as a string.');
        }

        return ClassLikeToken::fromFQCN($config['value']);
    }
}
