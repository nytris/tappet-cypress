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

use Tappet\Cypress\Automation\Matcher\ContextInterface;
use Tappet\Runner\Automation\Matcher\MatchHandlerInterface;
use Tappet\Runner\Matcher\MatcherInterface;

/**
 * Class MatchHandlerInitEvent.
 *
 * Fired during suite initialisation to allow plugins to register matcher handlers.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class MatchHandlerInitEvent extends AbstractInitEvent
{
    /**
     * Registers a handler contributing matcher FQCN(s) (e.g. Text, ExactText) for the given matcher type.
     * Note that the type will almost always be "default", unless overridden for a specific column/list/etc.
     *
     * @param MatchHandlerInterface<MatcherInterface, ContextInterface> $handler
     */
    public function registerMatchHandler(string $matcherType, MatchHandlerInterface $handler): void
    {
        $this->getAdapter()->getMatcherRegistry()->registerMatchHandler($matcherType, $handler);
    }
}
