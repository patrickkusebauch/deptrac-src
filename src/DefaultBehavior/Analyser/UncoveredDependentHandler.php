<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\DefaultBehavior\Analyser;

use JetBrains\PHPStormStub\PhpStormStubsMap;
use Qossmic\Deptrac\Contract\Analyser\ProcessEvent;
use Qossmic\Deptrac\Contract\Ast\AstMap\ClassLikeToken;
use Qossmic\Deptrac\Contract\Result\Uncovered;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class UncoveredDependentHandler implements EventSubscriberInterface
{
    public function __construct(private readonly bool $ignoreUncoveredInternalClasses) {}

    public function invoke(ProcessEvent $event): void
    {
        $dependent = $event->dependency->getDependent();
        $ruleset = $event->getResult();

        if ([] !== $event->dependentLayers) {
            return;
        }

        if ($dependent instanceof ClassLikeToken && !$this->ignoreUncoveredInternalClass($dependent)) {
            $ruleset->addRule(new Uncovered($event->dependency, $event->dependerLayer));
        }

        $event->stopPropagation();
    }

    private function ignoreUncoveredInternalClass(ClassLikeToken $token): bool
    {
        if (!$this->ignoreUncoveredInternalClasses) {
            return false;
        }

        $tokenString = $token->toString();

        return isset(PhpStormStubsMap::CLASSES[$tokenString]) || 'ReturnTypeWillChange' === $tokenString;
    }

    public static function getSubscribedEvents()
    {
        return [
            ProcessEvent::class => ['invoke', 2],
        ];
    }
}