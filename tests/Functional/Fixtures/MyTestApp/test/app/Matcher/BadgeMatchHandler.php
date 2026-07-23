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

namespace Tappet\Cypress\Tests\Functional\Fixtures\MyTestApp\test\app\Matcher;

use Tappet\Cypress\Automation\CypressAutomationInterface;
use Tappet\Cypress\Automation\Matcher\ContextInterface;
use Tappet\Runner\Automation\Matcher\MatchHandlerInterface;
use Tappet\Runner\Matcher\MatcherInterface;

/**
 * @implements MatchHandlerInterface<MatcherInterface, ContextInterface>
 */
class BadgeMatchHandler implements MatchHandlerInterface
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
            Badge::class => function (MatcherInterface $matcher, ContextInterface $context): void {
                /** @var Badge $matcher */
                $this->matchBadge($matcher, $context);
            },
        ];
    }

    public function matchBadge(Badge $matcher, ContextInterface $context): void
    {
        $attributePrefix = $this->automation->getAttributePrefix();
        $target = $this->automation->resolve($context);

        $target->find('[data-' . $attributePrefix . '-badge="' . $matcher->getHandle() . '"]')->should('exist');
    }
}
