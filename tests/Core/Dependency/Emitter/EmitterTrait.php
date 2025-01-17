<?php

declare(strict_types=1);

namespace Tests\Qossmic\Deptrac\Core\Dependency\Emitter;

use PhpParser\Lexer;
use PhpParser\ParserFactory;
use Qossmic\Deptrac\Contract\Dependency\DependencyEmitterInterface;
use Qossmic\Deptrac\Contract\Dependency\DependencyInterface;
use Qossmic\Deptrac\Core\Ast\AstLoader;
use Qossmic\Deptrac\Core\Ast\Parser\Cache\AstFileReferenceInMemoryCache;
use Qossmic\Deptrac\Core\Ast\Parser\NikicTypeResolver;
use Qossmic\Deptrac\Core\Dependency\DependencyList;
use Qossmic\Deptrac\DefaultBehavior\Ast\Extractors\AnonymousClassExtractor;
use Qossmic\Deptrac\DefaultBehavior\Ast\Extractors\ClassExtractor;
use Qossmic\Deptrac\DefaultBehavior\Ast\Extractors\FunctionCallExtractor;
use Qossmic\Deptrac\DefaultBehavior\Ast\Extractors\FunctionLikeExtractor;
use Qossmic\Deptrac\DefaultBehavior\Ast\Extractors\InstanceofExtractor;
use Qossmic\Deptrac\DefaultBehavior\Ast\Extractors\NewExtractor;
use Qossmic\Deptrac\DefaultBehavior\Ast\Extractors\PropertyExtractor;
use Qossmic\Deptrac\DefaultBehavior\Ast\Extractors\StaticCallExtractor;
use Qossmic\Deptrac\DefaultBehavior\Ast\Extractors\StaticPropertyFetchExtractor;
use Qossmic\Deptrac\DefaultBehavior\Ast\Extractors\TraitUseExtractor;
use Qossmic\Deptrac\DefaultBehavior\Ast\Extractors\UseExtractor;
use Qossmic\Deptrac\DefaultBehavior\Ast\Extractors\VariableExtractor;
use Qossmic\Deptrac\DefaultBehavior\Ast\Parser\NikicPhpParser;
use Symfony\Component\EventDispatcher\EventDispatcher;

trait EmitterTrait
{
    /**
     * @param string|string[] $files
     */
    public function getEmittedDependencies(DependencyEmitterInterface $emitter, $files): array
    {
        $files = (array) $files;

        $nikicTypeResolver = new NikicTypeResolver();
        $parser = new NikicPhpParser(
            (new ParserFactory())->create(ParserFactory::ONLY_PHP7, new Lexer()),
            new AstFileReferenceInMemoryCache(),
            [
                new AnonymousClassExtractor(),
                new FunctionLikeExtractor($nikicTypeResolver),
                new PropertyExtractor($nikicTypeResolver),
                new FunctionCallExtractor($nikicTypeResolver),
                new VariableExtractor($nikicTypeResolver),
                new ClassExtractor(),
                new UseExtractor(),
                new InstanceofExtractor($nikicTypeResolver),
                new StaticCallExtractor($nikicTypeResolver),
                new StaticPropertyFetchExtractor($nikicTypeResolver),
                new NewExtractor($nikicTypeResolver),
                new TraitUseExtractor($nikicTypeResolver),
            ]
        );
        $astMap = (new AstLoader($parser, new EventDispatcher()))->createAstMap($files);
        $result = new DependencyList();

        $emitter->applyDependencies($astMap, $result);

        return array_map(
            static function (DependencyInterface $d) {
                return sprintf('%s:%d on %s',
                    $d->getDepender()->toString(),
                    $d->getContext()->fileOccurrence->line,
                    $d->getDependent()->toString()
                );
            },
            $result->getDependenciesAndInheritDependencies()
        );
    }
}
