<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Contract\Ast\AstMap;

/**
 * @psalm-immutable
 */
final class DependencyToken
{
    public function __construct(
        public readonly TokenInterface $token,
        public readonly DependencyContext $context,
    ) {}
}
