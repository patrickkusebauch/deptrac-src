<?php

declare(strict_types=1);

namespace Tests\Qossmic\Deptrac\Core\Layer\Collector;

use PHPUnit\Framework\TestCase;
use Qossmic\Deptrac\Contract\Ast\AstMap\ClassLikeReference;
use Qossmic\Deptrac\Contract\Ast\AstMap\ClassLikeToken;
use Qossmic\Deptrac\Contract\Layer\InvalidCollectorDefinitionException;
use Qossmic\Deptrac\DefaultBehavior\Ast\Parser\NikicPhpParser;
use Qossmic\Deptrac\DefaultBehavior\Layer\MethodCollector;

final class MethodCollectorTest extends TestCase
{
    private NikicPhpParser $astParser;
    private MethodCollector $collector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->astParser = $this->createMock(NikicPhpParser::class);

        $this->collector = new MethodCollector($this->astParser);
    }

    public static function provideSatisfy(): iterable
    {
        yield [
            ['value' => 'abc'],
            [
                'abc',
                'abcdef',
                'xyz',
            ],
            true,
        ];

        yield [
            ['value' => 'abc'],
            [
                'abc',
                'xyz',
            ],
            true,
        ];

        yield [
            ['value' => 'abc'],
            [
                'xyz',
            ],
            false,
        ];
    }

    /**
     * @dataProvider provideSatisfy
     */
    public function testSatisfy(array $configuration, array $methods, bool $expected): void
    {
        $astClassReference = new ClassLikeReference(ClassLikeToken::fromFQCN('foo'));

        $this->astParser
            ->method('getMethodNamesForClassLikeReference')
            ->with($astClassReference)
            ->willReturn($methods)
        ;

        $actual = $this->collector->satisfy(
            $configuration,
            $astClassReference,
        );

        self::assertSame($expected, $actual);
    }

    public function testClassLikeAstNotFoundDoesNotSatisfy(): void
    {
        $astClassReference = new ClassLikeReference(ClassLikeToken::fromFQCN('foo'));
        $this->astParser
            ->method('getMethodNamesForClassLikeReference')
            ->with($astClassReference)
            ->willReturn([])
        ;

        $actual = $this->collector->satisfy(
            ['value' => 'abc'],
            $astClassReference,
        );

        self::assertFalse($actual);
    }

    public function testMissingNameThrowsException(): void
    {
        $astClassReference = new ClassLikeReference(ClassLikeToken::fromFQCN('foo'));

        $this->expectException(InvalidCollectorDefinitionException::class);
        $this->expectExceptionMessage('MethodCollector needs the name configuration.');

        $this->collector->satisfy(
            [],
            $astClassReference,
        );
    }

    public function testInvalidRegexParam(): void
    {
        $astClassReference = new ClassLikeReference(ClassLikeToken::fromFQCN('foo'));

        $this->expectException(InvalidCollectorDefinitionException::class);

        $this->collector->satisfy(
            ['value' => '/'],
            $astClassReference,
        );
    }
}
