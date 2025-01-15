<?php

declare(strict_types=1);

namespace Tests\Qossmic\Deptrac\Core\Ast;

use Closure;
use PhpParser\Lexer;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;
use Qossmic\Deptrac\Contract\Ast\AstMap\ClassLikeReference;
use Qossmic\Deptrac\Contract\Ast\AstMap\ClassLikeToken;
use Qossmic\Deptrac\Contract\Ast\AstMap\DependencyToken;
use Qossmic\Deptrac\Contract\Ast\ParserInterface;
use Qossmic\Deptrac\Core\Ast\AstLoader;
use Qossmic\Deptrac\Core\Ast\Parser\Cache\AstFileReferenceInMemoryCache;
use Qossmic\Deptrac\Core\Ast\Parser\Extractors\AnonymousClassExtractor;
use Qossmic\Deptrac\Core\Ast\Parser\Extractors\ClassConstantExtractor;
use Qossmic\Deptrac\Core\Ast\Parser\Extractors\ClassExtractor;
use Qossmic\Deptrac\Core\Ast\Parser\Extractors\GroupUseExtractor;
use Qossmic\Deptrac\Core\Ast\Parser\Extractors\TraitUseExtractor;
use Qossmic\Deptrac\Core\Ast\Parser\Extractors\UseExtractor;
use Qossmic\Deptrac\Core\Ast\Parser\NikicPhpParser\NikicPhpParser;
use Qossmic\Deptrac\Core\Ast\Parser\NikicPhpParser\NikicTypeResolver;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Tests\Qossmic\Deptrac\Core\Ast\Fixtures\BasicDependency\BasicDependencyClassB;
use Tests\Qossmic\Deptrac\Core\Ast\Fixtures\BasicDependency\BasicDependencyClassC;
use Tests\Qossmic\Deptrac\Core\Ast\Fixtures\BasicDependency\BasicDependencyTraitA;
use Tests\Qossmic\Deptrac\Core\Ast\Fixtures\BasicDependency\BasicDependencyTraitB;
use Tests\Qossmic\Deptrac\Core\Ast\Fixtures\BasicDependency\BasicDependencyTraitC;
use Tests\Qossmic\Deptrac\Core\Ast\Fixtures\BasicDependency\BasicDependencyTraitClass;
use Tests\Qossmic\Deptrac\Core\Ast\Fixtures\BasicDependency\BasicDependencyTraitD;

final class AstMapGeneratorTest extends TestCase
{
    /**
     * @dataProvider createParser
     */
    public function testBasicDependencyClass(Closure $parserBuilder): void
    {
        $filePath = __DIR__.'/Fixtures/BasicDependency/BasicDependencyClass.php';
        $parser = $parserBuilder($filePath);
        $astRunner = new AstLoader(
            $parser, new EventDispatcher()
        );

        $astMap = $astRunner->createAstMap([$filePath]);

        self::assertEqualsCanonicalizing(
            [
                'Tests\Qossmic\Deptrac\Core\Ast\Fixtures\BasicDependency\BasicDependencyClassA::9 (Extends)',
                'Tests\Qossmic\Deptrac\Core\Ast\Fixtures\BasicDependency\BasicDependencyClassInterfaceA::9 (Implements)',
            ],
            self::getInheritsAsString(
                $astMap->getClassReferenceForToken(ClassLikeToken::fromFQCN(BasicDependencyClassB::class))
            )
        );

        self::assertEqualsCanonicalizing(
            [
                'Tests\Qossmic\Deptrac\Core\Ast\Fixtures\BasicDependency\BasicDependencyClassInterfaceA::13 (Implements)',
                'Tests\Qossmic\Deptrac\Core\Ast\Fixtures\BasicDependency\BasicDependencyClassInterfaceB::13 (Implements)',
            ],
            self::getInheritsAsString(
                $astMap->getClassReferenceForToken(ClassLikeToken::fromFQCN(BasicDependencyClassC::class))
            )
        );
    }

    /**
     * @dataProvider createParser
     */
    public function testBasicTraitsClass(Closure $parserBuilder): void
    {
        $filePath = __DIR__.'/Fixtures/BasicDependency/BasicDependencyTraits.php';
        $parser = $parserBuilder($filePath);
        $astRunner = new AstLoader(
            $parser, new EventDispatcher()
        );

        $astMap = $astRunner->createAstMap([$filePath]);

        self::assertEqualsCanonicalizing(
            [],
            self::getInheritsAsString(
                $astMap->getClassReferenceForToken(ClassLikeToken::fromFQCN(BasicDependencyTraitA::class))
            )
        );

        self::assertEqualsCanonicalizing(
            [],
            self::getInheritsAsString(
                $astMap->getClassReferenceForToken(ClassLikeToken::fromFQCN(BasicDependencyTraitB::class))
            )
        );

        self::assertEqualsCanonicalizing(
            ['Tests\Qossmic\Deptrac\Core\Ast\Fixtures\BasicDependency\BasicDependencyTraitB::7 (Uses)'],
            self::getInheritsAsString(
                $astMap->getClassReferenceForToken(ClassLikeToken::fromFQCN(BasicDependencyTraitC::class))
            )
        );

        self::assertEqualsCanonicalizing(
            [
                'Tests\Qossmic\Deptrac\Core\Ast\Fixtures\BasicDependency\BasicDependencyTraitA::10 (Uses)',
                'Tests\Qossmic\Deptrac\Core\Ast\Fixtures\BasicDependency\BasicDependencyTraitB::11 (Uses)',
            ],
            self::getInheritsAsString(
                $astMap->getClassReferenceForToken(ClassLikeToken::fromFQCN(BasicDependencyTraitD::class))
            )
        );

        self::assertEqualsCanonicalizing(
            ['Tests\Qossmic\Deptrac\Core\Ast\Fixtures\BasicDependency\BasicDependencyTraitA::15 (Uses)'],
            self::getInheritsAsString(
                $astMap->getClassReferenceForToken(ClassLikeToken::fromFQCN(BasicDependencyTraitClass::class))
            )
        );
    }

    /**
     * @dataProvider createParser
     */
    public function testIssue319(Closure $parserBuilder): void
    {
        $filePath = __DIR__.'/Fixtures/Issue319.php';
        $parser = $parserBuilder($filePath);
        $astRunner = new AstLoader(
            $parser, new EventDispatcher()
        );

        $astMap = $astRunner->createAstMap([$filePath]);

        self::assertSame(
            [
                'Foo\Exception',
                'Foo\RuntimeException',
                'LogicException',
            ],
            array_map(
                static function (DependencyToken $dependency) {
                    return $dependency->token->toString();
                },
                $astMap->getFileReferences()[$filePath]->dependencies
            )
        );
    }

    /**
     * @return string[]
     */
    private static function getInheritsAsString(?ClassLikeReference $classReference): array
    {
        if (null === $classReference) {
            return [];
        }

        return array_map('strval', $classReference->inherits);
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
            new AnonymousClassExtractor(),
            new ClassConstantExtractor(),
            new ClassExtractor(),
            new UseExtractor(),
            new GroupUseExtractor(),
            new TraitUseExtractor($typeResolver),
        ];

        return new NikicPhpParser(
            (new ParserFactory())->create(
                ParserFactory::ONLY_PHP7,
                new Lexer()
            ), $cache, $extractors
        );
    }
}
