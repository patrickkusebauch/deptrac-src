<?php

declare(strict_types=1);

namespace Tests\Qossmic\Deptrac\Core\Layer\Collector;

use PHPUnit\Framework\TestCase;
use Qossmic\Deptrac\Contract\Ast\AstMap\ClassLikeReference;
use Qossmic\Deptrac\Contract\Ast\AstMap\ClassLikeToken;
use Qossmic\Deptrac\Contract\Ast\AstMap\FunctionReference;
use Qossmic\Deptrac\Contract\Ast\AstMap\FunctionToken;
use Qossmic\Deptrac\Contract\Ast\AstMap\TokenReferenceInterface;
use Qossmic\Deptrac\DefaultBehavior\Layer\PhpInternalCollector;

final class PHPInternalCollectorTest extends TestCase
{
    /**
     * @return iterable<array{array{value:string}, TokenReferenceInterface, bool}>
     */
    public static function provideSatisfy(): iterable
    {
        yield [['value' => '^PDO'], new ClassLikeReference(ClassLikeToken::fromFQCN('PDOException')), true];
        yield [['value' => '^PFO'], new ClassLikeReference(ClassLikeToken::fromFQCN('PDOException')), false];
        yield [['value' => '.*'], new ClassLikeReference(ClassLikeToken::fromFQCN('PDOExceptionNonExistent')), false];
        yield [['value' => '^pdo'], new FunctionReference(FunctionToken::fromFQCN('pdo_drivers')), true];
        yield [['value' => '^pfo'], new FunctionReference(FunctionToken::fromFQCN('pdo_drivers')), false];
        yield [['value' => '.*'], new FunctionReference(FunctionToken::fromFQCN('pdo_drivers_non_existent')), false];
    }

    /**
     * @dataProvider provideSatisfy
     */
    public function testSatisfy(array $config, TokenReferenceInterface $reference, bool $expected): void
    {
        $collector = new PhpInternalCollector();
        $actual = $collector->satisfy(
            $config,
            $reference,
        );

        self::assertSame($expected, $actual);
    }
}
