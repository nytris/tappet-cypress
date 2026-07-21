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

namespace Tappet\Cypress\Automation\State;

use Tappet\Cypress\Automation\CypressAutomationInterface;
use Tappet\Runner\Assertion\StateAssertionInterface;
use Tappet\Runner\Automation\State\StateAssertionHandlerInterface;
use Tappet\Runner\Standard\Assertion\ExpectState;

/**
 * Class ExistsStateAssertionHandler.
 *
 * Handles assertions on state.
 *
 * @implements StateAssertionHandlerInterface<StateAssertionInterface>
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class ExistsStateAssertionHandler implements StateAssertionHandlerInterface
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
            ExpectState::class => function (StateAssertionInterface $assertion): void {
                /** @var ExpectState $assertion */
                $this->assertStateExists($assertion);
            },
        ];
    }

    /**
     * Asserts that the specified state exists.
     */
    public function assertStateExists(ExpectState $assertion): void
    {
        $attributePrefix = $this->automation->getAttributePrefix();
        $cy = $this->automation->getCy();

        $cy->get('[data-' . $attributePrefix . '-state="' . $assertion->getStateHandle() . '"]')->should('exist');
    }
}
