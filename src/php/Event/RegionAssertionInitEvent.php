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
use Tappet\Runner\Assertion\RegionAssertionInterface;
use Tappet\Runner\Automation\Matcher\MatcherRegistryInterface;
use Tappet\Runner\Automation\Region\RegionAssertionHandlerInterface;

/**
 * Class RegionAssertionInitEvent.
 *
 * Fired during suite initialisation to allow plugins to register region assertion handlers.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class RegionAssertionInitEvent extends AbstractInitEvent
{
    /**
     * Fetches the registry of matcher handlers, useful for constructor-injecting into
     * a region assertion handler that itself dispatches to matchers (e.g. TableRegionAssertionHandler).
     *
     * @return MatcherRegistryInterface<ContextInterface>
     */
    public function getMatcherRegistry(): MatcherRegistryInterface
    {
        return $this->getAdapter()->getMatcherRegistry();
    }

    /**
     * Registers a handler for the given region type.
     *
     * @param RegionAssertionHandlerInterface<RegionAssertionInterface> $handler
     */
    public function registerRegionAssertionHandler(string $regionType, RegionAssertionHandlerInterface $handler): void
    {
        $this->getAdapter()->getRegionAssertionRegistry()->registerRegionAssertionHandler($regionType, $handler);
    }
}
