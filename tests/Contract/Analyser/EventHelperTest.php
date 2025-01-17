<?php

declare(strict_types=1);

namespace Tests\Qossmic\Deptrac\Contract\Analyser;

use PHPUnit\Framework\TestCase;
use Qossmic\Deptrac\Contract\Analyser\EventHelper;
use Qossmic\Deptrac\Contract\Ast\AstMap\ClassLikeToken;
use Qossmic\Deptrac\Contract\OutputFormatter\BaselineMapperInterface;
use Qossmic\Deptrac\Core\Layer\LayerProvider;

final class EventHelperTest extends TestCase
{
    public function testIsViolationSkipped(): void
    {
        $configuration = [
            'ClassWithOneDep' => [
                'DependencyClass',
            ],
            'ClassWithEmptyDeps' => [],
            'ClassWithMultipleDeps' => [
                'DependencyClass1',
                'DependencyClass2',
                'DependencyClass2',
            ],
        ];

        $baselineMapper = new class($configuration) implements BaselineMapperInterface {
            public function __construct(private readonly array $violations) {}

            public function fromPHPListToString(array $groupedViolations): string
            {
                return '';
            }

            public function loadViolations(): array
            {
                return $this->violations;
            }
        };

        $helper = new EventHelper(new LayerProvider([]), $baselineMapper);

        self::assertTrue(
            $helper->shouldViolationBeSkipped(
                ClassLikeToken::fromFQCN('ClassWithOneDep')->toString(),
                ClassLikeToken::fromFQCN('DependencyClass')->toString()
            )
        );
        // also skips multiple occurrences
        self::assertTrue(
            $helper->shouldViolationBeSkipped(
                ClassLikeToken::fromFQCN('ClassWithOneDep')->toString(),
                ClassLikeToken::fromFQCN('DependencyClass')->toString()
            )
        );
        self::assertFalse(
            $helper->shouldViolationBeSkipped(
                ClassLikeToken::fromFQCN('ClassWithEmptyDeps')->toString(),
                ClassLikeToken::fromFQCN('DependencyClass')->toString()
            )
        );
        self::assertTrue(
            $helper->shouldViolationBeSkipped(
                ClassLikeToken::fromFQCN('ClassWithMultipleDeps')->toString(),
                ClassLikeToken::fromFQCN('DependencyClass1')->toString()
            )
        );
        self::assertTrue(
            $helper->shouldViolationBeSkipped(
                ClassLikeToken::fromFQCN('ClassWithMultipleDeps')->toString(),
                ClassLikeToken::fromFQCN('DependencyClass2')->toString()
            )
        );
        self::assertFalse(
            $helper->shouldViolationBeSkipped(
                ClassLikeToken::fromFQCN('DependencyClass')->toString(),
                ClassLikeToken::fromFQCN('ClassWithOneDep')->toString()
            )
        );
    }

    public function testUnmatchedSkippedViolations(): void
    {
        $configuration = [
            'ClassWithOneDep' => [
                'DependencyClass',
            ],
            'ClassWithEmptyDeps' => [],
            'ClassWithMultipleDeps' => [
                'DependencyClass1',
                'DependencyClass2',
                'DependencyClass2',
            ],
        ];

        $baselineMapper = new class($configuration) implements BaselineMapperInterface {
            public function __construct(private readonly array $violations) {}

            public function fromPHPListToString(array $groupedViolations): string
            {
                return '';
            }

            public function loadViolations(): array
            {
                return $this->violations;
            }
        };

        $helper = new EventHelper(new LayerProvider([]), $baselineMapper);

        self::assertTrue(
            $helper->shouldViolationBeSkipped(
                ClassLikeToken::fromFQCN('ClassWithOneDep')->toString(),
                ClassLikeToken::fromFQCN('DependencyClass')->toString()
            )
        );
        // also skips multiple occurrences
        self::assertTrue(
            $helper->shouldViolationBeSkipped(
                ClassLikeToken::fromFQCN('ClassWithOneDep')->toString(),
                ClassLikeToken::fromFQCN('DependencyClass')->toString()
            )
        );
        self::assertSame(
            [
                'ClassWithMultipleDeps' => [
                    'DependencyClass1',
                    'DependencyClass2',
                    'DependencyClass2',
                ],
            ],
            $helper->unmatchedSkippedViolations()
        );
    }
}
