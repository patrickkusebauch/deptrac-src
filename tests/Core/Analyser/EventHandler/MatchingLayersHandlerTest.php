<?php

declare(strict_types=1);

namespace Tests\Qossmic\Deptrac\Core\Analyser\EventHandler;

use PHPUnit\Framework\TestCase;
use Qossmic\Deptrac\Contract\Analyser\ProcessEvent;
use Qossmic\Deptrac\DefaultBehavior\Analyser\MatchingLayersHandler;

class MatchingLayersHandlerTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $subscribedEvents = MatchingLayersHandler::getSubscribedEvents();

        self::assertCount(1, $subscribedEvents);
        self::assertArrayHasKey(ProcessEvent::class, $subscribedEvents);
        self::assertSame(['invoke', 1], $subscribedEvents[ProcessEvent::class]);
    }
}
