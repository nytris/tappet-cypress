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

use Tappet\Runner\Assertion\RemoteStateAssertionInterface;
use Tappet\Runner\Automation\RemoteState\RemoteStateAssertionHandlerInterface;

/**
 * Class RemoteStateAssertionInitEvent.
 *
 * Fired during suite initialisation to allow plugins to register remote state assertion handlers.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class RemoteStateAssertionInitEvent extends AbstractInitEvent
{
    /**
     * Registers a handler for one or more remote state assertion classes.
     *
     * @param RemoteStateAssertionHandlerInterface<RemoteStateAssertionInterface> $handler
     */
    public function registerRemoteStateAssertionHandler(RemoteStateAssertionHandlerInterface $handler): void
    {
        $this->getAdapter()->getRemoteStateAssertionRegistry()->registerRemoteStateAssertionHandler($handler);
    }
}
