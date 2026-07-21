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

use Tappet\Common\Event\EventDispatcherInterface;
use Tappet\Cypress\Automation\CypressAutomationInterface;
use Tappet\Cypress\Automation\Matcher\ContextInterface;
use Tappet\Cypress\Automation\Resolver\TypeResolverInterface;
use Tappet\Runner\Automation\Field\FieldActionRegistryInterface;
use Tappet\Runner\Automation\Field\FieldAssertionRegistryInterface;
use Tappet\Runner\Automation\Interaction\InteractionRegistryInterface;
use Tappet\Runner\Automation\Matcher\MatcherRegistryInterface;
use Tappet\Runner\Automation\Region\RegionAssertionRegistryInterface;
use Tappet\Runner\Automation\State\StateAssertionRegistryInterface;
use Tappet\Runner\Transition\Log\TransitionLogInterface;

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
    public function getAutomation(mixed $cy, TransitionLogInterface $transitionLog): CypressAutomationInterface;

    /**
     * Fetches the event dispatcher.
     */
    public function getEventDispatcher(): EventDispatcherInterface;

    /**
     * Fetches the registry of field action handlers.
     *
     * @return FieldActionRegistryInterface
     */
    public function getFieldActionRegistry(): FieldActionRegistryInterface;

    /**
     * Fetches the registry of field assertion handlers.
     *
     * @return FieldAssertionRegistryInterface
     */
    public function getFieldAssertionRegistry(): FieldAssertionRegistryInterface;

    /**
     * Fetches the registry of interaction handlers.
     *
     * @return InteractionRegistryInterface
     */
    public function getInteractionRegistry(): InteractionRegistryInterface;

    /**
     * Fetches the registry of matcher handlers.
     *
     * @return MatcherRegistryInterface<ContextInterface>
     */
    public function getMatcherRegistry(): MatcherRegistryInterface;

    /**
     * Fetches the registry of region assertion handlers.
     *
     * @return RegionAssertionRegistryInterface
     */
    public function getRegionAssertionRegistry(): RegionAssertionRegistryInterface;

    /**
     * Fetches the registry of state assertion handlers.
     *
     * @return StateAssertionRegistryInterface
     */
    public function getStateAssertionRegistry(): StateAssertionRegistryInterface;

    /**
     * Fetches the type resolver.
     */
    public function getTypeResolver(): TypeResolverInterface;
}
