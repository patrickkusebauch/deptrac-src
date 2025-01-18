<?php

declare(strict_types=1);

namespace Tests\Qossmic\Deptrac\Core\Ast\Parser;

use Closure;
use PhpParser\Lexer;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;
use Qossmic\Deptrac\Contract\Ast\AstMap\ClassLikeReference;
use Qossmic\Deptrac\Contract\Ast\AstMap\DependencyToken;
use Qossmic\Deptrac\Contract\Ast\ParserInterface;
use Qossmic\Deptrac\Core\Ast\Parser\Cache\AstFileReferenceInMemoryCache;
use Qossmic\Deptrac\Core\Ast\Parser\NikicTypeResolver;
use Qossmic\Deptrac\DefaultBehavior\Ast\Extractors\FunctionLikeExtractor;
use Qossmic\Deptrac\DefaultBehavior\Ast\Parser\NikicPhpParser;

final class FunctionLikeExtractorTest extends TestCase
{
    /**
     * @dataProvider createParser
     */
    public function testPropertyDependencyResolving(Closure $parserBuilder): void
    {
        $filePath = __DIR__.'/Fixtures/MethodSignatures.php';
        $parser = $parserBuilder($filePath);
        $astFileReference = $parser->parseFile($filePath);

        $astClassReferences = $astFileReference->classLikeReferences;

        self::assertCount(4, $astClassReferences);
        [$attribute, $classA, $classB, $classC] = $astClassReferences;

        self::assertEqualsCanonicalizing(
            [
                'Tests\Qossmic\Deptrac\Core\Ast\Parser\Fixtures\MethodAttribute::9 (attribute)',
            ],
            $this->getDependenciesAsString($classA)
        );

        self::assertEqualsCanonicalizing(
            [
                'Tests\Qossmic\Deptrac\Core\Ast\Parser\Fixtures\MethodSignaturesA::15 (returntype)',
            ],
            $this->getDependenciesAsString($classB)
        );

        self::assertEqualsCanonicalizing(
            [
                'Tests\Qossmic\Deptrac\Core\Ast\Parser\Fixtures\MethodSignaturesB::24 (parameter)',
                // NOTE: We are not yet tracking the call from MethodSignatureC::test()
                // to MethodSignatureA::foo().
            ],
            $this->getDependenciesAsString($classC)
        );
    }

    /**
     * @return string[]
     */
    private function getDependenciesAsString(?ClassLikeReference $classReference): array
    {
        if (null === $classReference) {
            return [];
        }

        return array_map(
            static function (DependencyToken $dependency) {
                return "{$dependency->token->toString()}::{$dependency->context->fileOccurrence->line} ({$dependency->context->dependencyType->value})";
            },
            $classReference->dependencies
        );
    }

    /**
     * @return list<array{ParserInterface}>
     */
    public static function createParser(): array
    {
        return [
            'Nikic Parser' => [self::createNikicParser(...)],
        ];
    }

    public static function createNikicParser(string $filePath): NikicPhpParser
    {
        $typeResolver = new NikicTypeResolver();

        $cache = new AstFileReferenceInMemoryCache();
        $extractors = [
            new FunctionLikeExtractor($typeResolver),
        ];

        return new NikicPhpParser(
            (new ParserFactory())->create(
                ParserFactory::ONLY_PHP7,
                new Lexer()
            ), $cache, $extractors
        );
    }
}
