<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\DefaultBehavior\Layer;

use Qossmic\Deptrac\Contract\Ast\AstMap\ClassLikeType;
use Qossmic\Deptrac\DefaultBehavior\Layer\Helpers\AbstractTypeCollector;

final class ClassCollector extends AbstractTypeCollector
{
    protected function getType(): ClassLikeType
    {
        return ClassLikeType::TYPE_CLASS;
    }
}
