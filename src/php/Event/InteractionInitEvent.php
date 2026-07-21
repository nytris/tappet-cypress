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

use Tappet\Runner\Action\InteractionInterface;
use Tappet\Runner\Automation\Interaction\InteractionHandlerInterface;

/**
 * Class InteractionInitEvent.
 *
 * Fired during suite initialisation to allow plugins to register interaction handlers.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class InteractionInitEvent extends AbstractInitEvent
{
    /**
     * Registers a handler for the given interaction type.
     *
     * @param InteractionHandlerInterface<InteractionInterface> $handler
     */
    public function registerInteractionHandler(string $interactionType, InteractionHandlerInterface $handler): void
    {
        $this->getAdapter()->getInteractionRegistry()->registerInteractionHandler($interactionType, $handler);
    }
}
