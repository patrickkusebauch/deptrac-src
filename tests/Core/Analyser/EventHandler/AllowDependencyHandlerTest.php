<?php

declare(strict_types=1);

namespace Tests\Qossmic\Deptrac\Core\Analyser\EventHandler;

use PHPUnit\Framework\TestCase;
use Qossmic\Deptrac\Contract\Analyser\ProcessEvent;
use Qossmic\Deptrac\DefaultBehavior\Analyser\AllowDependencyHandler;

class AllowDependencyHandlerTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $subscribedEvents = AllowDependencyHandler::getSubscribedEvents();

        self::assertCount(1, $subscribedEvents);
        self::assertArrayHasKey(ProcessEvent::class, $subscribedEvents);
        self::assertSame(['invoke', -100], $subscribedEvents[ProcessEvent::class]);
    }
}
