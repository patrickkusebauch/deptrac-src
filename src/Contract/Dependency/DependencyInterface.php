<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Contract\Dependency;

use Qossmic\Deptrac\Contract\Ast\AstMap\DependencyContext;
use Qossmic\Deptrac\Contract\Ast\AstMap\TokenInterface;

/**
 * Represents a dependency between 2 tokens (depender and dependent).
 */
interface DependencyInterface
{
    public function getDepender(): TokenInterface;

    public function getDependent(): TokenInterface;

    public function getContext(): DependencyContext;

    /**
     * @return array<array{name:string, line:int}>
     */
    public function serialize(): array;
}
