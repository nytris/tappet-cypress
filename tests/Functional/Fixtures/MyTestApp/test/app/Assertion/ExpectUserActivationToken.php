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

namespace Tappet\Cypress\Tests\Functional\Fixtures\MyTestApp\test\app\Assertion;

use Tappet\Runner\Assertion\RemoteStateAssertionInterface;
use Tappet\Runner\Environment\EnvironmentInterface;
use Tappet\Runner\Matcher\MatcherInterface;

/**
 * Class ExpectUserActivationToken.
 *
 * Asserts on the activation token of the currently logged-in user - a value deliberately
 * never surfaced anywhere in the UI, so must instead be fetched from the backend via
 * a Tappet API remote state endpoint.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class ExpectUserActivationToken implements RemoteStateAssertionInterface
{
    public function __construct(
        private readonly MatcherInterface $matcher
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getMatcher(): MatcherInterface
    {
        return $this->matcher;
    }

    /**
     * @inheritDoc
     */
    public function getRemoteStateHandle(): string
    {
        return 'user-activation-token';
    }

    /**
     * @inheritDoc
     */
    public function perform(EnvironmentInterface $environment): void
    {
        $environment->performRemoteStateAssertion($this);
    }
}
