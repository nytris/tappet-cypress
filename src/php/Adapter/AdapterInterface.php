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

namespace Tappet\Cypress\Adapter;

use Tappet\Core\Automation\Field\FieldActionRegistryInterface;
use Tappet\Core\Automation\Interaction\InteractionRegistryInterface;
use Tappet\Core\Automation\Region\RegionAssertionRegistryInterface;
use Tappet\Core\Automation\State\StateAssertionRegistryInterface;
use Tappet\Cypress\Automation\CypressAutomation;

/**
 * Interface AdapterInterface.
 *
 * Encapsulates the implementation of Tappet Cypress, allowing the behaviour
 * to be overridden via e.g. `tappet.cypress.config.php`.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
interface AdapterInterface
{
    /**
     * Fetches the Cypress automation implementation.
     */
    public function getAutomation(mixed $cy): CypressAutomation;

    /**
     * Fetches the registry of field action handlers.
     */
    public function getFieldActionRegistry(): FieldActionRegistryInterface;

    /**
     * Fetches the registry of interaction handlers.
     */
    public function getInteractionRegistry(): InteractionRegistryInterface;

    /**
     * Fetches the registry of region assertion handlers.
     */
    public function getRegionAssertionRegistry(): RegionAssertionRegistryInterface;

    /**
     * Fetches the registry of state assertion handlers.
     */
    public function getStateAssertionRegistry(): StateAssertionRegistryInterface;
}
