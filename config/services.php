<?php

declare(strict_types=1);

use PhpParser\Lexer;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use Psr\EventDispatcher\EventDispatcherInterface;
use Qossmic\Deptrac\Contract\Analyser\EventHelper;
use Qossmic\Deptrac\Contract\Ast\AstFileReferenceCacheInterface;
use Qossmic\Deptrac\Contract\Ast\AstMapExtractorInterface;
use Qossmic\Deptrac\Contract\Ast\ParserInterface;
use Qossmic\Deptrac\Contract\Ast\TypeResolverInterface;
use Qossmic\Deptrac\Contract\Config\CollectorType;
use Qossmic\Deptrac\Contract\Config\EmitterType;
use Qossmic\Deptrac\Contract\Layer\CollectorResolverInterface;
use Qossmic\Deptrac\Contract\Layer\LayerProviderInterface;
use Qossmic\Deptrac\Contract\Layer\LayerResolverInterface;
use Qossmic\Deptrac\Contract\OutputFormatter\BaselineMapperInterface;
use Qossmic\Deptrac\Core\Analyser\DependencyLayersAnalyser;
use Qossmic\Deptrac\Core\Analyser\LayerDependenciesAnalyser;
use Qossmic\Deptrac\Core\Analyser\LayerForTokenAnalyser;
use Qossmic\Deptrac\Core\Analyser\RulesetUsageAnalyser;
use Qossmic\Deptrac\Core\Analyser\TokenInLayerAnalyser;
use Qossmic\Deptrac\Core\Analyser\UnassignedTokenAnalyser;
use Qossmic\Deptrac\Core\Ast\AstLoader;
use Qossmic\Deptrac\Core\Ast\AstMapExtractor;
use Qossmic\Deptrac\Core\Ast\Parser\Cache\AstFileReferenceInMemoryCache;
use Qossmic\Deptrac\Core\Ast\Parser\NikicTypeResolver;
use Qossmic\Deptrac\Core\Dependency\DependencyResolver;
use Qossmic\Deptrac\Core\Dependency\TokenResolver;
use Qossmic\Deptrac\Core\InputCollector\FileInputCollector;
use Qossmic\Deptrac\Core\InputCollector\InputCollectorInterface;
use Qossmic\Deptrac\Core\Layer\CollectorProvider;
use Qossmic\Deptrac\Core\Layer\CollectorResolver;
use Qossmic\Deptrac\Core\Layer\LayerProvider;
use Qossmic\Deptrac\Core\Layer\LayerResolver;
use Qossmic\Deptrac\DefaultBehavior\Analyser\AllowDependencyHandler;
use Qossmic\Deptrac\DefaultBehavior\Analyser\DependsOnDisallowedLayer;
use Qossmic\Deptrac\DefaultBehavior\Analyser\DependsOnInternalToken;
use Qossmic\Deptrac\DefaultBehavior\Analyser\DependsOnPrivateLayer;
use Qossmic\Deptrac\DefaultBehavior\Analyser\MatchingLayersHandler;
use Qossmic\Deptrac\DefaultBehavior\Analyser\UncoveredDependentHandler;
use Qossmic\Deptrac\DefaultBehavior\Analyser\UnmatchedSkippedViolations;
use Qossmic\Deptrac\DefaultBehavior\Ast\Extractors\AnonymousClassExtractor;
use Qossmic\Deptrac\DefaultBehavior\Ast\Extractors\CatchExtractor;
use Qossmic\Deptrac\DefaultBehavior\Ast\Extractors\ClassConstantExtractor;
use Qossmic\Deptrac\DefaultBehavior\Ast\Extractors\ClassExtractor;
use Qossmic\Deptrac\DefaultBehavior\Ast\Extractors\ClassLikeExtractor;
use Qossmic\Deptrac\DefaultBehavior\Ast\Extractors\ClassMethodExtractor;
use Qossmic\Deptrac\DefaultBehavior\Ast\Extractors\FunctionCallExtractor;
use Qossmic\Deptrac\DefaultBehavior\Ast\Extractors\FunctionLikeExtractor;
use Qossmic\Deptrac\DefaultBehavior\Ast\Extractors\GroupUseExtractor;
use Qossmic\Deptrac\DefaultBehavior\Ast\Extractors\InstanceofExtractor;
use Qossmic\Deptrac\DefaultBehavior\Ast\Extractors\InterfaceExtractor;
use Qossmic\Deptrac\DefaultBehavior\Ast\Extractors\NewExtractor;
use Qossmic\Deptrac\DefaultBehavior\Ast\Extractors\PropertyExtractor;
use Qossmic\Deptrac\DefaultBehavior\Ast\Extractors\StaticCallExtractor;
use Qossmic\Deptrac\DefaultBehavior\Ast\Extractors\StaticPropertyFetchExtractor;
use Qossmic\Deptrac\DefaultBehavior\Ast\Extractors\TraitUseExtractor;
use Qossmic\Deptrac\DefaultBehavior\Ast\Extractors\UseExtractor;
use Qossmic\Deptrac\DefaultBehavior\Ast\Extractors\VariableExtractor;
use Qossmic\Deptrac\DefaultBehavior\Ast\Parser\NikicPhpParser;
use Qossmic\Deptrac\DefaultBehavior\Dependency\ClassDependencyEmitter;
use Qossmic\Deptrac\DefaultBehavior\Dependency\ClassSuperglobalDependencyEmitter;
use Qossmic\Deptrac\DefaultBehavior\Dependency\FileDependencyEmitter;
use Qossmic\Deptrac\DefaultBehavior\Dependency\FunctionCallDependencyEmitter;
use Qossmic\Deptrac\DefaultBehavior\Dependency\FunctionDependencyEmitter;
use Qossmic\Deptrac\DefaultBehavior\Dependency\FunctionSuperglobalDependencyEmitter;
use Qossmic\Deptrac\DefaultBehavior\Dependency\UsesDependencyEmitter;
use Qossmic\Deptrac\DefaultBehavior\Layer\AttributeCollector;
use Qossmic\Deptrac\DefaultBehavior\Layer\BoolCollector;
use Qossmic\Deptrac\DefaultBehavior\Layer\ClassCollector;
use Qossmic\Deptrac\DefaultBehavior\Layer\ClassLikeCollector;
use Qossmic\Deptrac\DefaultBehavior\Layer\ClassNameRegexCollector;
use Qossmic\Deptrac\DefaultBehavior\Layer\ComposerCollector;
use Qossmic\Deptrac\DefaultBehavior\Layer\DirectoryCollector;
use Qossmic\Deptrac\DefaultBehavior\Layer\ExtendsCollector;
use Qossmic\Deptrac\DefaultBehavior\Layer\FunctionNameCollector;
use Qossmic\Deptrac\DefaultBehavior\Layer\GlobCollector;
use Qossmic\Deptrac\DefaultBehavior\Layer\ImplementsCollector;
use Qossmic\Deptrac\DefaultBehavior\Layer\InheritanceLevelCollector;
use Qossmic\Deptrac\DefaultBehavior\Layer\InheritsCollector;
use Qossmic\Deptrac\DefaultBehavior\Layer\InterfaceCollector;
use Qossmic\Deptrac\DefaultBehavior\Layer\LayerCollector;
use Qossmic\Deptrac\DefaultBehavior\Layer\MethodCollector;
use Qossmic\Deptrac\DefaultBehavior\Layer\PhpInternalCollector;
use Qossmic\Deptrac\DefaultBehavior\Layer\SuperglobalCollector;
use Qossmic\Deptrac\DefaultBehavior\Layer\TagValueRegexCollector;
use Qossmic\Deptrac\DefaultBehavior\Layer\TraitCollector;
use Qossmic\Deptrac\DefaultBehavior\Layer\UsesCollector;
use Qossmic\Deptrac\DefaultBehavior\OutputFormatter\BaselineOutputFormatter;
use Qossmic\Deptrac\DefaultBehavior\OutputFormatter\CodeclimateOutputFormatter;
use Qossmic\Deptrac\DefaultBehavior\OutputFormatter\ConsoleOutputFormatter;
use Qossmic\Deptrac\DefaultBehavior\OutputFormatter\GithubActionsOutputFormatter;
use Qossmic\Deptrac\DefaultBehavior\OutputFormatter\GraphVizOutputDisplayFormatter;
use Qossmic\Deptrac\DefaultBehavior\OutputFormatter\GraphVizOutputDotFormatter;
use Qossmic\Deptrac\DefaultBehavior\OutputFormatter\GraphVizOutputHtmlFormatter;
use Qossmic\Deptrac\DefaultBehavior\OutputFormatter\GraphVizOutputImageFormatter;
use Qossmic\Deptrac\DefaultBehavior\OutputFormatter\Helpers\FormatterConfiguration;
use Qossmic\Deptrac\DefaultBehavior\OutputFormatter\JsonOutputFormatter;
use Qossmic\Deptrac\DefaultBehavior\OutputFormatter\JUnitOutputFormatter;
use Qossmic\Deptrac\DefaultBehavior\OutputFormatter\MermaidJSOutputFormatter;
use Qossmic\Deptrac\DefaultBehavior\OutputFormatter\TableOutputFormatter;
use Qossmic\Deptrac\DefaultBehavior\OutputFormatter\XMLOutputFormatter;
use Qossmic\Deptrac\Supportive\Console\Command\AnalyseCommand;
use Qossmic\Deptrac\Supportive\Console\Command\AnalyseRunner;
use Qossmic\Deptrac\Supportive\Console\Command\ChangedFilesCommand;
use Qossmic\Deptrac\Supportive\Console\Command\ChangedFilesRunner;
use Qossmic\Deptrac\Supportive\Console\Command\DebugDependenciesCommand;
use Qossmic\Deptrac\Supportive\Console\Command\DebugDependenciesRunner;
use Qossmic\Deptrac\Supportive\Console\Command\DebugLayerCommand;
use Qossmic\Deptrac\Supportive\Console\Command\DebugLayerRunner;
use Qossmic\Deptrac\Supportive\Console\Command\DebugTokenCommand;
use Qossmic\Deptrac\Supportive\Console\Command\DebugTokenRunner;
use Qossmic\Deptrac\Supportive\Console\Command\DebugUnassignedCommand;
use Qossmic\Deptrac\Supportive\Console\Command\DebugUnassignedRunner;
use Qossmic\Deptrac\Supportive\Console\Command\DebugUnusedCommand;
use Qossmic\Deptrac\Supportive\Console\Command\DebugUnusedRunner;
use Qossmic\Deptrac\Supportive\Console\Command\InitCommand;
use Qossmic\Deptrac\Supportive\File\Dumper;
use Qossmic\Deptrac\Supportive\File\YmlFileLoader;
use Qossmic\Deptrac\Supportive\OutputFormatter\FormatterProvider;
use Qossmic\Deptrac\Supportive\OutputFormatter\YamlBaselineMapper;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\EventDispatcher\EventDispatcher;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_locator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services
        ->defaults()
        ->autowire()
    ;

    /*
     * Utilities
     */
    $services->set(EventDispatcher::class);
    $services->alias(EventDispatcherInterface::class, EventDispatcher::class);
    $services->alias(Symfony\Component\EventDispatcher\EventDispatcherInterface::class, EventDispatcher::class);
    $services->alias('event_dispatcher', EventDispatcher::class);
    $services
        ->set(FileInputCollector::class)
        ->args([
            '$paths' => param('paths'),
            '$excludedFilePatterns' => param('exclude_files'),
            '$basePath' => param('projectDirectory'),
        ])
    ;
    $services->alias(InputCollectorInterface::class, FileInputCollector::class);
    $services->set(YmlFileLoader::class);
    $services
        ->set(Dumper::class)
        ->args([
            '$templateFile' => __DIR__.'/deptrac_template.yaml',
        ])
    ;

    /*
     * AST
     */
    $services->set(AstLoader::class);
    $services->set(ParserFactory::class);
    $services->set(Lexer::class);
    $services
        ->set(Parser::class)
        ->factory([service(ParserFactory::class), 'create'])
        ->args([
            '$kind' => ParserFactory::PREFER_PHP7,
        ])
    ;
    $services->set(AstFileReferenceInMemoryCache::class);
    $services->alias(AstFileReferenceCacheInterface::class, AstFileReferenceInMemoryCache::class);
    $services
        ->set(NikicPhpParser::class)
        ->args([
            '$extractors' => tagged_iterator('reference_extractors'),
        ])
    ;
    $services->alias(ParserInterface::class, NikicPhpParser::class);
    $services->set(NikicTypeResolver::class);
    $services->alias(TypeResolverInterface::class, NikicTypeResolver::class);
    $services
        ->set(AnonymousClassExtractor::class)
        ->tag('reference_extractors')
    ;
    $services
        ->set(CatchExtractor::class)
        ->tag('reference_extractors')
    ;
    $services
        ->set(ClassConstantExtractor::class)
        ->tag('reference_extractors')
    ;
    $services
        ->set(ClassExtractor::class)
        ->tag('reference_extractors')
    ;
    $services
        ->set(ClassLikeExtractor::class)
        ->tag('reference_extractors')
    ;
    $services
        ->set(ClassMethodExtractor::class)
        ->tag('reference_extractors')
    ;
    $services
        ->set(FunctionCallExtractor::class)
        ->tag('reference_extractors')
    ;
    $services
        ->set(FunctionLikeExtractor::class)
        ->tag('reference_extractors')
    ;
    $services
        ->set(GroupUseExtractor::class)
        ->tag('reference_extractors')
    ;
    $services
        ->set(InstanceofExtractor::class)
        ->tag('reference_extractors')
    ;
    $services
        ->set(InterfaceExtractor::class)
        ->tag('reference_extractors')
    ;
    $services
        ->set(NewExtractor::class)
        ->tag('reference_extractors')
    ;
    $services
        ->set(PropertyExtractor::class)
        ->tag('reference_extractors')
    ;
    $services
        ->set(StaticCallExtractor::class)
        ->tag('reference_extractors')
    ;
    $services
        ->set(StaticPropertyFetchExtractor::class)
        ->tag('reference_extractors')
    ;
    $services
        ->set(TraitUseExtractor::class)
        ->tag('reference_extractors')
    ;
    $services
        ->set(UseExtractor::class)
        ->tag('reference_extractors')
    ;
    $services
        ->set(VariableExtractor::class)
        ->tag('reference_extractors')
    ;

    /*
     * Dependency
     */
    $services
        ->set(DependencyResolver::class)
        ->args([
            '$config' => param('analyser'),
            '$emitterLocator' => tagged_locator('dependency_emitter', 'key'),
        ])
    ;
    $services->set(TokenResolver::class);
    $services
        ->set(ClassDependencyEmitter::class)
        ->tag('dependency_emitter', ['key' => EmitterType::CLASS_TOKEN->value])
    ;
    $services
        ->set(ClassSuperglobalDependencyEmitter::class)
        ->tag('dependency_emitter', ['key' => EmitterType::CLASS_SUPERGLOBAL_TOKEN->value])
    ;
    $services
        ->set(FileDependencyEmitter::class)
        ->tag('dependency_emitter', ['key' => EmitterType::FILE_TOKEN->value])
    ;
    $services
        ->set(FunctionDependencyEmitter::class)
        ->tag('dependency_emitter', ['key' => EmitterType::FUNCTION_TOKEN->value])
    ;
    $services
        ->set(FunctionCallDependencyEmitter::class)
        ->tag('dependency_emitter', ['key' => EmitterType::FUNCTION_CALL->value])
    ;
    $services
        ->set(FunctionSuperglobalDependencyEmitter::class)
        ->tag('dependency_emitter', ['key' => EmitterType::FUNCTION_SUPERGLOBAL_TOKEN->value])
    ;
    $services
        ->set(UsesDependencyEmitter::class)
        ->tag('dependency_emitter', ['key' => EmitterType::USE_TOKEN->value])
    ;

    /*
     * Layer
     */
    $services
        ->set(LayerResolver::class)
        ->args([
            '$layersConfig' => param('layers'),
        ])
    ;
    $services->alias(LayerResolverInterface::class, LayerResolver::class);
    $services
        ->set(CollectorProvider::class)
        ->args([
            '$collectorLocator' => tagged_locator('collector', 'type'),
        ])
    ;
    $services->set(CollectorResolver::class);
    $services->alias(CollectorResolverInterface::class, CollectorResolver::class);
    $services
        ->set(AttributeCollector::class)
        ->tag('collector', ['type' => CollectorType::TYPE_ATTRIBUTE->value])
    ;
    $services
        ->set(BoolCollector::class)
        ->tag('collector', ['type' => CollectorType::TYPE_BOOL->value])
    ;
    $services
        ->set(ClassCollector::class)
        ->tag('collector', ['type' => CollectorType::TYPE_CLASS->value])
    ;
    $services
        ->set(ClassLikeCollector::class)
        ->tag('collector', ['type' => CollectorType::TYPE_CLASSLIKE->value])
    ;
    $services
        ->set(ClassNameRegexCollector::class)
        ->tag('collector', ['type' => CollectorType::TYPE_CLASS_NAME_REGEX->value])
    ;
    $services
        ->set(TagValueRegexCollector::class)
        ->tag('collector', ['type' => CollectorType::TYPE_TAG_VALUE_REGEX->value])
    ;
    $services
        ->set(DirectoryCollector::class)
        ->tag('collector', ['type' => CollectorType::TYPE_DIRECTORY->value])
    ;
    $services
        ->set(ExtendsCollector::class)
        ->tag('collector', ['type' => CollectorType::TYPE_EXTENDS->value])
    ;
    $services
        ->set(FunctionNameCollector::class)
        ->tag('collector', ['type' => CollectorType::TYPE_FUNCTION_NAME->value])
    ;
    $services
        ->set(GlobCollector::class)
        ->args([
            '$basePath' => param('projectDirectory'),
        ])
        ->tag('collector', ['type' => CollectorType::TYPE_GLOB->value])
    ;
    $services
        ->set(ImplementsCollector::class)
        ->tag('collector', ['type' => CollectorType::TYPE_IMPLEMENTS->value])
    ;
    $services
        ->set(InheritanceLevelCollector::class)
        ->tag('collector', ['type' => CollectorType::TYPE_INHERITANCE->value])
    ;
    $services
        ->set(InterfaceCollector::class)
        ->tag('collector', ['type' => CollectorType::TYPE_INTERFACE->value])
    ;
    $services
        ->set(InheritsCollector::class)
        ->tag('collector', ['type' => CollectorType::TYPE_INHERITS->value])
    ;
    $services
        ->set(LayerCollector::class)
        ->tag('collector', ['type' => CollectorType::TYPE_LAYER->value])
    ;
    $services
        ->set(MethodCollector::class)
        ->tag('collector', ['type' => CollectorType::TYPE_METHOD->value])
    ;
    $services
        ->set(SuperglobalCollector::class)
        ->tag('collector', ['type' => CollectorType::TYPE_SUPERGLOBAL->value])
    ;
    $services
        ->set(TraitCollector::class)
        ->tag('collector', ['type' => CollectorType::TYPE_TRAIT->value])
    ;
    $services
        ->set(UsesCollector::class)
        ->tag('collector', ['type' => CollectorType::TYPE_USES->value])
    ;
    $services
        ->set(PhpInternalCollector::class)
        ->tag('collector', ['type' => CollectorType::TYPE_PHP_INTERNAL->value])
    ;
    $services
        ->set(ComposerCollector::class)
        ->tag('collector', ['type' => CollectorType::TYPE_COMPOSER->value])
    ;

    /*
     * Analyser
     */
    $services->set(AstMapExtractor::class);
    $services->alias(AstMapExtractorInterface::class, AstMapExtractor::class);
    $services
        ->set(UncoveredDependentHandler::class)
        ->args([
            '$ignoreUncoveredInternalClasses' => param('ignore_uncovered_internal_classes'),
        ])
        ->tag('kernel.event_subscriber')
    ;
    $services
        ->set(MatchingLayersHandler::class)
        ->tag('kernel.event_subscriber')
    ;
    $services
        ->set(LayerProvider::class)
        ->args([
            '$allowedLayers' => param('ruleset'),
        ])
    ;
    $services->alias(LayerProviderInterface::class, LayerProvider::class);
    $services
        ->set(AllowDependencyHandler::class)
        ->tag('kernel.event_subscriber')
    ;
    $services
        ->set(DependsOnDisallowedLayer::class)
        ->tag('kernel.event_subscriber')
    ;
    $services
        ->set(DependsOnPrivateLayer::class)
        ->tag('kernel.event_subscriber')
    ;
    $services
        ->set(DependsOnInternalToken::class)
        ->tag('kernel.event_subscriber')
        ->args([
            '$config' => param('analyser'),
        ])
    ;
    $services
        ->set(UnmatchedSkippedViolations::class)
        ->tag('kernel.event_subscriber')
    ;
    $services->set(YamlBaselineMapper::class)
        ->args([
            '$skippedViolations' => param('skip_violations'),
        ])
    ;
    $services->alias(BaselineMapperInterface::class, YamlBaselineMapper::class);
    $services->set(EventHelper::class);
    $services
        ->set(DependencyLayersAnalyser::class)
    ;
    $services->set(TokenInLayerAnalyser::class)
        ->args([
            '$config' => param('analyser'),
        ])
    ;
    $services->set(LayerForTokenAnalyser::class);
    $services->set(UnassignedTokenAnalyser::class)
        ->args([
            '$config' => param('analyser'),
        ])
    ;
    $services->set(LayerDependenciesAnalyser::class);
    $services->set(RulesetUsageAnalyser::class)
        ->args([
            '$layers' => param('layers'),
        ])
    ;

    /*
     * OutputFormatter
     */
    $services
        ->set(FormatterConfiguration::class)
        ->args([
            '$config' => param('formatters'),
        ])
    ;
    $services
        ->set(FormatterProvider::class)
        ->args([
            '$formatterLocator' => tagged_locator('output_formatter', null, 'getName'),
        ])
    ;
    $services
        ->set(ConsoleOutputFormatter::class)
        ->tag('output_formatter')
    ;
    $services
        ->set(GithubActionsOutputFormatter::class)
        ->tag('output_formatter')
    ;
    $services
        ->set(JUnitOutputFormatter::class)
        ->tag('output_formatter')
    ;
    $services
        ->set(TableOutputFormatter::class)
        ->tag('output_formatter')
    ;
    $services
        ->set(XMLOutputFormatter::class)
        ->tag('output_formatter')
    ;
    $services
        ->set(BaselineOutputFormatter::class)
        ->tag('output_formatter')
    ;
    $services
        ->set(JsonOutputFormatter::class)
        ->tag('output_formatter')
    ;
    $services
        ->set(GraphVizOutputDisplayFormatter::class)
        ->tag('output_formatter')
    ;
    $services
        ->set(GraphVizOutputImageFormatter::class)
        ->tag('output_formatter')
    ;
    $services
        ->set(GraphVizOutputDotFormatter::class)
        ->tag('output_formatter')
    ;
    $services
        ->set(GraphVizOutputHtmlFormatter::class)
        ->tag('output_formatter')
    ;
    $services
        ->set(CodeclimateOutputFormatter::class)
        ->tag('output_formatter')
    ;
    $services
        ->set(MermaidJSOutputFormatter::class)
        ->tag('output_formatter')
    ;

    /*
     * Console
     */
    $services
        ->set(InitCommand::class)
        ->autowire()
        ->tag('console.command')
    ;
    $services
        ->set(AnalyseRunner::class)
        ->autowire()
    ;
    $services
        ->set(AnalyseCommand::class)
        ->autowire()
        ->tag('console.command')
    ;
    $services
        ->set(ChangedFilesRunner::class)
        ->autowire()
    ;
    $services
        ->set(ChangedFilesCommand::class)
        ->autowire()
        ->tag('console.command')
    ;
    $services
        ->set(DebugLayerRunner::class)
        ->autowire()
        ->args([
            '$layers' => param('layers'),
        ])
    ;
    $services
        ->set(DebugLayerCommand::class)
        ->autowire()
        ->tag('console.command')
    ;
    $services
        ->set(DebugTokenRunner::class)
        ->autowire()
    ;
    $services
        ->set(DebugTokenCommand::class)
        ->autowire()
        ->tag('console.command')
    ;
    $services
        ->set(DebugUnassignedRunner::class)
        ->autowire()
    ;
    $services
        ->set(DebugUnassignedCommand::class)
        ->autowire()
        ->tag('console.command')
    ;
    $services
        ->set(DebugDependenciesRunner::class)
        ->autowire()
    ;
    $services
        ->set(DebugDependenciesCommand::class)
        ->autowire()
        ->tag('console.command')
    ;
    $services
        ->set(DebugUnusedRunner::class)
        ->autowire()
    ;
    $services
        ->set(DebugUnusedCommand::class)
        ->autowire()
        ->tag('console.command')
    ;
};
