<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Contract\Ast\AstMap;

enum ClassLikeType: string implements TokenInterface
{
    case TYPE_CLASSLIKE = 'classLike';
    case TYPE_CLASS = 'class';
    case TYPE_INTERFACE = 'interface';
    case TYPE_TRAIT = 'trait';

    public function toString(): string
    {
        return $this->value;
    }
}
