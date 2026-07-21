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

use Tappet\Common\Event\EventInterface;
use Tappet\Cypress\Adapter\AdapterInterface;
use Tappet\Cypress\Automation\CypressAutomationInterface;

/**
 * Class AbstractInitEvent.
 *
 * Base class for the events dispatched during suite initialisation,
 * allowing plugins to register handlers etc.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
abstract class AbstractInitEvent implements EventInterface
{
    public function __construct(
        private readonly AdapterInterface $adapter,
        private readonly CypressAutomationInterface $automation
    ) {
    }

    /**
     * Fetches the adapter, giving access to its registries and other services
     * useful for injecting into a handler's constructor.
     */
    public function getAdapter(): AdapterInterface
    {
        return $this->adapter;
    }

    /**
     * Fetches the automation, useful for injecting into a handler's constructor.
     */
    public function getAutomation(): CypressAutomationInterface
    {
        return $this->automation;
    }
}
