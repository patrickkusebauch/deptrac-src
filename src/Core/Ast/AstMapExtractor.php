<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Core\Ast;

use Qossmic\Deptrac\Contract\Ast\AstException;
use Qossmic\Deptrac\Contract\Ast\AstMapExtractorInterface;
use Qossmic\Deptrac\Core\InputCollector\InputCollectorInterface;
use Qossmic\Deptrac\Core\InputCollector\InputException;

class AstMapExtractor implements AstMapExtractorInterface
{
    private ?AstMap $astMapCache = null;

    public function __construct(
        private readonly InputCollectorInterface $inputCollector,
        private readonly AstLoader $astLoader,
    ) {}

    /**
     * @throws AstException
     */
    public function extract(): AstMap
    {
        try {
            return $this->astMapCache ??= $this->astLoader->createAstMap($this->inputCollector->collect());
        } catch (InputException $exception) {
            throw AstException::couldNotCollectFiles($exception);
        }
    }
}
