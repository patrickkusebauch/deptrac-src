<?php

declare(strict_types=1);

namespace Tests\Qossmic\Deptrac\Core\Dependency;

use PHPUnit\Framework\TestCase;
use Qossmic\Deptrac\Contract\Ast\AstMap\AstInherit;
use Qossmic\Deptrac\Contract\Ast\AstMap\AstInheritType;
use Qossmic\Deptrac\Contract\Ast\AstMap\ClassLikeToken;
use Qossmic\Deptrac\Contract\Ast\AstMap\DependencyContext;
use Qossmic\Deptrac\Contract\Ast\AstMap\DependencyType;
use Qossmic\Deptrac\Contract\Ast\AstMap\FileOccurrence;
use Qossmic\Deptrac\Core\Dependency\InheritDependency;
use Qossmic\Deptrac\DefaultBehavior\Dependency\Helpers\Dependency;

final class InheritDependencyTest extends TestCase
{
    public function testGetSet(): void
    {
        $classLikeNameA = ClassLikeToken::fromFQCN('a');
        $classLikeNameB = ClassLikeToken::fromFQCN('b');
        $fileOccurrence = new FileOccurrence('a.php', 1);

        $dependency = new InheritDependency(
            $classLikeNameA,
            $classLikeNameB,
            $dep = new Dependency($classLikeNameA, $classLikeNameB, new DependencyContext(
                $fileOccurrence, DependencyType::PARAMETER)),
            $astInherit = new AstInherit($classLikeNameB, $fileOccurrence, AstInheritType::EXTENDS)
        );

        self::assertSame($classLikeNameA, $dependency->getDepender());
        self::assertSame($classLikeNameB, $dependency->getDependent());
        self::assertSame(1, $dependency->getContext()->fileOccurrence->line);
        self::assertSame($dep, $dependency->originalDependency);
        self::assertSame($astInherit, $dependency->inheritPath);
    }
}
