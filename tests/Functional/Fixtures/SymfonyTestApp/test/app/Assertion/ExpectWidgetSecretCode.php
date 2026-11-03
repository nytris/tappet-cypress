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

namespace Tappet\Cypress\Tests\Functional\Fixtures\SymfonyTestApp\test\app\Assertion;

use Tappet\Runner\Assertion\RemoteStateAssertionInterface;
use Tappet\Runner\Environment\EnvironmentInterface;
use Tappet\Runner\Matcher\MatcherInterface;

/**
 * Class ExpectWidgetSecretCode.
 *
 * Asserts on the secret code of the most recently loaded widget - a value deliberately
 * never rendered by WidgetController, so must instead be fetched from the real
 * tappet/bundle-backed API via a remote state assertion.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class ExpectWidgetSecretCode implements RemoteStateAssertionInterface
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
        return 'widget-secret-code';
    }

    /**
     * @inheritDoc
     */
    public function perform(EnvironmentInterface $environment): void
    {
        $environment->performRemoteStateAssertion($this);
    }
}
