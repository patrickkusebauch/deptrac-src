<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\DefaultBehavior\Layer;

use Qossmic\Deptrac\Contract\Ast\AstException;
use Qossmic\Deptrac\Contract\Ast\AstMap\AstMapInterface;
use Qossmic\Deptrac\Contract\Ast\AstMap\ClassLikeReference;
use Qossmic\Deptrac\Contract\Ast\AstMap\TokenReferenceInterface;
use Qossmic\Deptrac\Contract\Ast\AstMapExtractorInterface;
use Qossmic\Deptrac\Contract\Ast\CouldNotParseFileException;
use Qossmic\Deptrac\Contract\Layer\CollectorInterface;
use Qossmic\Deptrac\Contract\Layer\InvalidCollectorDefinitionException;

final class InheritanceLevelCollector implements CollectorInterface
{
    private AstMapInterface $astMap;

    public function __construct(private readonly AstMapExtractorInterface $astMapExtractor) {}

    public function satisfy(array $config, TokenReferenceInterface $reference): bool
    {
        if (!$reference instanceof ClassLikeReference) {
            return false;
        }

        try {
            $this->astMap ??= $this->astMapExtractor->extract();
        } catch (AstException $exception) {
            throw CouldNotParseFileException::because('Could not build Ast map', $exception);
        }
        $classInherits = $this->astMap->getClassInherits($reference->getToken());
        if (!isset($config['value']) || !is_numeric($config['value'])) {
            throw InvalidCollectorDefinitionException::invalidCollectorConfiguration('InheritanceLevelCollector needs inheritance depth as int.');
        }

        $depth = (int) $config['value'];
        foreach ($classInherits as $classInherit) {
            if (count($classInherit->getPath()) >= $depth) {
                return true;
            }
        }

        return false;
    }
}
