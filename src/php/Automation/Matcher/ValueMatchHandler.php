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

namespace Tappet\Cypress\Automation\Matcher;

use Tappet\Cypress\Automation\CypressAutomationInterface;
use Tappet\Runner\Automation\Matcher\MatchHandlerInterface;
use Tappet\Runner\Exception\AssertionFailedException;
use Tappet\Runner\Matcher\MatcherInterface;
use Tappet\Runner\Standard\Matcher\ExactText;
use Tappet\Runner\Standard\Matcher\Text;

/**
 * Class ValueMatchHandler.
 *
 * Handles matching for the built-in Text and ExactText matchers against a raw PHP value
 * (e.g. a string fetched via a remote state assertion) rather than a Cypress DOM chainable -
 * unlike TextMatchHandler, matching is performed directly in PHP rather than delegated to
 * a Cypress `.should()`/`.contains()` command, since there is no DOM element to query.
 *
 * @implements MatchHandlerInterface<MatcherInterface, ContextInterface>
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class ValueMatchHandler implements MatchHandlerInterface
{
    public function __construct(
        private readonly CypressAutomationInterface $automation
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getHandlers(): array
    {
        return [
            ExactText::class => function (MatcherInterface $matcher, ContextInterface $context): void {
                /** @var ExactText $matcher */
                $this->matchExactText($matcher, $context);
            },
            Text::class => function (MatcherInterface $matcher, ContextInterface $context): void {
                /** @var Text $matcher */
                $this->matchText($matcher, $context);
            },
        ];
    }

    /**
     * Asserts that the target value equals the matcher's text exactly.
     */
    public function matchExactText(ExactText $matcher, ContextInterface $context): void
    {
        $value = $this->automation->resolve($context);

        if ($value !== $matcher->getText()) {
            throw new AssertionFailedException(sprintf(
                'Expected value to equal "%s" exactly but got "%s"',
                $matcher->getText(),
                (string) $value
            ));
        }
    }

    /**
     * Asserts that the target value contains the matcher's text.
     */
    public function matchText(Text $matcher, ContextInterface $context): void
    {
        $value = $this->automation->resolve($context);

        if (!str_contains((string) $value, $matcher->getText())) {
            throw new AssertionFailedException(sprintf(
                'Expected value "%s" to contain "%s"',
                (string) $value,
                $matcher->getText()
            ));
        }
    }
}
