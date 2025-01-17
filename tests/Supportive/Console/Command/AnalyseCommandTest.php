<?php

declare(strict_types=1);

namespace Tests\Qossmic\Deptrac\Supportive\Console\Command;

use PHPUnit\Framework\TestCase;
use Qossmic\Deptrac\DefaultBehavior\OutputFormatter\GithubActionsOutputFormatter;
use Qossmic\Deptrac\Supportive\Console\Command\AnalyseCommand;

class AnalyseCommandTest extends TestCase
{
    protected function setUp(): void
    {
        putenv('GITHUB_ACTIONS=true');
        parent::setUp();
    }

    public function testDefaultFormatterForGithubActions(): void
    {
        self::assertSame(GithubActionsOutputFormatter::getName(), AnalyseCommand::getDefaultFormatter());
    }
}
