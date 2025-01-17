<?php

declare(strict_types=1);

namespace Tests\Qossmic\Deptrac\Core\Dependency;

use PHPUnit\Framework\TestCase;
use Qossmic\Deptrac\Contract\Ast\AstMap\ClassLikeToken;
use Qossmic\Deptrac\Contract\Ast\AstMap\DependencyContext;
use Qossmic\Deptrac\Contract\Ast\AstMap\DependencyType;
use Qossmic\Deptrac\Contract\Ast\AstMap\FileOccurrence;
use Qossmic\Deptrac\DefaultBehavior\Dependency\Helpers\Dependency;

final class DependencyTest extends TestCase
{
    public function testGetSet(): void
    {
        $dependency = new Dependency(
            ClassLikeToken::fromFQCN('a'),
            ClassLikeToken::fromFQCN('b'), new DependencyContext(new FileOccurrence('/foo.php', 23), DependencyType::PARAMETER
            ));
        self::assertSame('a', $dependency->getDepender()->toString());
        self::assertSame('/foo.php', $dependency->getContext()->fileOccurrence->filepath);
        self::assertSame(23, $dependency->getContext()->fileOccurrence->line);
        self::assertSame('b', $dependency->getDependent()->toString());
    }
}
