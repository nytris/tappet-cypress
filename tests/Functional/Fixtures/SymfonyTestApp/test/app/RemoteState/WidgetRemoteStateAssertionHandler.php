<?php

/*
 * Tappet Cypress - Enjoyable GUI testing with Tappet, using Cypress
 * Copyright (c) Dan Phillimore (asmblah)
 * https://github.com/nytris/tappet-cypress/
 *
 * Released under the MIT license.
 * https://github.com/nytris/tappet-cypress/raw/main/MIT-LICENSE.txt
 */

declare(strict_types=1);

namespace Tappet\Cypress\Tests\Functional\Fixtures\SymfonyTestApp\test\app\RemoteState;

use Tappet\Cypress\Automation\CypressAutomationInterface;
use Tappet\Cypress\Automation\Matcher\Context;
use Tappet\Cypress\Automation\Matcher\ContextInterface;
use Tappet\Cypress\Tests\Functional\Fixtures\SymfonyTestApp\test\app\Assertion\ExpectWidgetSecretCode;
use Tappet\Runner\Assertion\RemoteStateAssertionInterface;
use Tappet\Runner\Automation\Matcher\MatcherRegistryInterface;
use Tappet\Runner\Automation\RemoteState\RemoteStateAssertionHandlerInterface;

/**
 * Class WidgetRemoteStateAssertionHandler.
 *
 * Handles ExpectWidgetSecretCode by fetching the value from the backend via the real
 * tappet/bundle's remote state endpoint, then dispatching it (wrapped as a raw value,
 * not a DOM chainable) to the matcher registry's "value" match handlers.
 *
 * @implements RemoteStateAssertionHandlerInterface<RemoteStateAssertionInterface>
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class WidgetRemoteStateAssertionHandler implements RemoteStateAssertionHandlerInterface
{
    /**
     * @param MatcherRegistryInterface<ContextInterface> $matcherRegistry
     */
    public function __construct(
        private readonly CypressAutomationInterface $automation,
        private readonly MatcherRegistryInterface $matcherRegistry
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getHandlers(): array
    {
        return [
            ExpectWidgetSecretCode::class => function (RemoteStateAssertionInterface $assertion): void {
                /** @var ExpectWidgetSecretCode $assertion */
                $this->assertWidgetSecretCode($assertion);
            },
        ];
    }

    /**
     * Fetches the widget's secret code from the backend and matches it.
     */
    public function assertWidgetSecretCode(ExpectWidgetSecretCode $assertion): void
    {
        $value = $this->automation->fetchRemoteState($assertion->getRemoteStateHandle());

        $this->matcherRegistry->handleMatcher('value', $assertion->getMatcher(), new Context($value));
    }
}
