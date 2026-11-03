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

namespace Tappet\Cypress\Tests\Functional\Fixtures\MyTestApp\test\app\RemoteState;

use Tappet\Cypress\Automation\CypressAutomationInterface;
use Tappet\Cypress\Automation\Matcher\Context;
use Tappet\Cypress\Automation\Matcher\ContextInterface;
use Tappet\Cypress\Tests\Functional\Fixtures\MyTestApp\test\app\Assertion\ExpectUserActivationToken;
use Tappet\Runner\Assertion\RemoteStateAssertionInterface;
use Tappet\Runner\Automation\Matcher\MatcherRegistryInterface;
use Tappet\Runner\Automation\RemoteState\RemoteStateAssertionHandlerInterface;

/**
 * Class UserRemoteStateAssertionHandler.
 *
 * Handles ExpectUserActivationToken by fetching the value from the backend via the
 * Tappet API's remote state endpoint, then dispatching it (wrapped as a raw value,
 * not a DOM chainable) to the matcher registry's "value" match handlers.
 *
 * @implements RemoteStateAssertionHandlerInterface<RemoteStateAssertionInterface>
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class UserRemoteStateAssertionHandler implements RemoteStateAssertionHandlerInterface
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
            ExpectUserActivationToken::class => function (RemoteStateAssertionInterface $assertion): void {
                /** @var ExpectUserActivationToken $assertion */
                $this->assertUserActivationToken($assertion);
            },
        ];
    }

    /**
     * Fetches the user's activation token from the backend and matches it.
     */
    public function assertUserActivationToken(ExpectUserActivationToken $assertion): void
    {
        $value = $this->automation->fetchRemoteState($assertion->getRemoteStateHandle());

        $this->matcherRegistry->handleMatcher('value', $assertion->getMatcher(), new Context($value));
    }
}
