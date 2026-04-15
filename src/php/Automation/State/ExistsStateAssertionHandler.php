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

use Tappet\Core\Assertion\StateAssertionInterface;
use Tappet\Core\Automation\AutomationInterface;
use Tappet\Core\Automation\State\StateAssertionHandlerInterface;
use Tappet\Core\Standard\Assertion\ExpectState;
use Tappet\Cypress\Automation\CypressAutomation;

/**
 * Class ExistsStateAssertionHandler.
 *
 * Handles assertions on state.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class ExistsStateAssertionHandler implements StateAssertionHandlerInterface
{
    /**
     * @inheritDoc
     */
    public function getHandlers(): array
    {
        return [
            ExpectState::class => function (StateAssertionInterface $assertion, AutomationInterface $automation): void {
                /** @var ExpectState $assertion */
                /** @var CypressAutomation $automation */
                $this->assertStateExists($assertion, $automation);
            },
        ];
    }

    /**
     * Asserts that the specified state exists.
     */
    public function assertStateExists(ExpectState $assertion, CypressAutomation $automation): void
    {
        $cy = $automation->getCy();

        $cy->get('[data-tappet-state="' . $assertion->getStateHandle() . '"]')->should('exist');
    }
}
