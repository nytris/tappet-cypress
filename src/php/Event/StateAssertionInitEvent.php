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

namespace Tappet\Cypress\Event;

use Tappet\Runner\Assertion\StateAssertionInterface;
use Tappet\Runner\Automation\State\StateAssertionHandlerInterface;

/**
 * Class StateAssertionInitEvent.
 *
 * Fired during suite initialisation to allow plugins to register state assertion handlers.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class StateAssertionInitEvent extends AbstractInitEvent
{
    /**
     * Registers a handler for the given state type.
     *
     * @param StateAssertionHandlerInterface<StateAssertionInterface> $handler
     */
    public function registerStateAssertionHandler(string $stateType, StateAssertionHandlerInterface $handler): void
    {
        $this->getAdapter()->getStateAssertionRegistry()->registerStateAssertionHandler($stateType, $handler);
    }
}
