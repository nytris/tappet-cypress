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
use Tappet\Runner\Matcher\MatcherInterface;
use Tappet\Runner\Standard\Matcher\ExactText;
use Tappet\Runner\Standard\Matcher\Text;

/**
 * Class TextMatchHandler.
 *
 * Handles matching for the built-in Text and ExactText matchers.
 *
 * @implements MatchHandlerInterface<MatcherInterface, ContextInterface>
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class TextMatchHandler implements MatchHandlerInterface
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
     * Asserts that the target's text contents equal the matcher's text exactly.
     */
    public function matchExactText(ExactText $matcher, ContextInterface $context): void
    {
        $target = $this->automation->resolve($context);

        $target->should('have.text', $matcher->getText());
    }

    /**
     * Asserts that the target contains the matcher's text.
     */
    public function matchText(Text $matcher, ContextInterface $context): void
    {
        $target = $this->automation->resolve($context);

        $target->contains($matcher->getText());
    }
}
