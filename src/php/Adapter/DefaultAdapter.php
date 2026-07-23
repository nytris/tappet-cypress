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

use Tappet\Common\Event\EventDispatcher;
use Tappet\Common\Event\EventDispatcherInterface;
use Tappet\Cypress\Automation\CypressAutomation;
use Tappet\Cypress\Automation\Matcher\ContextInterface;
use Tappet\Cypress\Automation\Resolver\TypeResolver;
use Tappet\Cypress\Automation\Resolver\TypeResolverInterface;
use Tappet\Runner\Automation\Field\FieldActionRegistry;
use Tappet\Runner\Automation\Field\FieldActionRegistryInterface;
use Tappet\Runner\Automation\Field\FieldAssertionRegistry;
use Tappet\Runner\Automation\Field\FieldAssertionRegistryInterface;
use Tappet\Runner\Automation\Interaction\InteractionRegistry;
use Tappet\Runner\Automation\Interaction\InteractionRegistryInterface;
use Tappet\Runner\Automation\Matcher\MatcherRegistry;
use Tappet\Runner\Automation\Matcher\MatcherRegistryInterface;
use Tappet\Runner\Automation\Region\RegionAssertionRegistry;
use Tappet\Runner\Automation\Region\RegionAssertionRegistryInterface;
use Tappet\Runner\Automation\State\StateAssertionRegistry;
use Tappet\Runner\Automation\State\StateAssertionRegistryInterface;
use Tappet\Runner\Transition\Log\TransitionLogInterface;

/**
 * Class DefaultAdapter.
 *
 * Encapsulates the implementation of Tappet Cypress, allowing the behaviour
 * to be overridden via e.g. `tappet.cypress.config.php`.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class DefaultAdapter implements AdapterInterface
{
    /**
     * @var FieldActionRegistryInterface
     */
    private readonly FieldActionRegistryInterface $fieldActionRegistry;

    /**
     * @var FieldAssertionRegistryInterface
     */
    private readonly FieldAssertionRegistryInterface $fieldAssertionRegistry;

    /**
     * @var InteractionRegistryInterface
     */
    private readonly InteractionRegistryInterface $interactionRegistry;

    /**
     * @var MatcherRegistryInterface<ContextInterface>
     */
    private readonly MatcherRegistryInterface $matcherRegistry;

    /**
     * @var RegionAssertionRegistryInterface
     */
    private readonly RegionAssertionRegistryInterface $regionAssertionRegistry;

    /**
     * @var StateAssertionRegistryInterface
     */
    private readonly StateAssertionRegistryInterface $stateAssertionRegistry;

    /**
     * @param FieldActionRegistryInterface|null $fieldActionRegistry
     * @param FieldAssertionRegistryInterface|null $fieldAssertionRegistry
     * @param InteractionRegistryInterface|null $interactionRegistry
     * @param MatcherRegistryInterface<ContextInterface>|null $matcherRegistry
     * @param RegionAssertionRegistryInterface|null $regionAssertionRegistry
     * @param StateAssertionRegistryInterface|null $stateAssertionRegistry
     * @param EventDispatcherInterface $eventDispatcher
     * @param string $attributePrefix
     */
    public function __construct(
        FieldActionRegistryInterface|null $fieldActionRegistry = null,
        FieldAssertionRegistryInterface|null $fieldAssertionRegistry = null,
        InteractionRegistryInterface|null $interactionRegistry = null,
        MatcherRegistryInterface|null $matcherRegistry = null,
        RegionAssertionRegistryInterface|null $regionAssertionRegistry = null,
        StateAssertionRegistryInterface|null $stateAssertionRegistry = null,
        private readonly TypeResolverInterface $typeResolver = new TypeResolver(),
        private readonly EventDispatcherInterface $eventDispatcher = new EventDispatcher(),
        private readonly string $attributePrefix = 'ui'
    ) {
        $this->fieldActionRegistry = $fieldActionRegistry ?? new FieldActionRegistry();
        $this->fieldAssertionRegistry = $fieldAssertionRegistry ?? new FieldAssertionRegistry();
        $this->interactionRegistry = $interactionRegistry ?? new InteractionRegistry();

        /** @var MatcherRegistryInterface<ContextInterface> $resolvedMatcherRegistry */
        $resolvedMatcherRegistry = $matcherRegistry ?? new MatcherRegistry();
        $this->matcherRegistry = $resolvedMatcherRegistry;

        $this->regionAssertionRegistry = $regionAssertionRegistry ?? new RegionAssertionRegistry();
        $this->stateAssertionRegistry = $stateAssertionRegistry ?? new StateAssertionRegistry();
    }

    /**
     * @inheritDoc
     */
    public function getAutomation(mixed $cy, TransitionLogInterface $transitionLog): CypressAutomation
    {
        return new CypressAutomation(
            $this->fieldActionRegistry,
            $this->fieldAssertionRegistry,
            $this->interactionRegistry,
            $this->regionAssertionRegistry,
            $this->stateAssertionRegistry,
            $this->typeResolver,
            $cy,
            $transitionLog,
            $this->attributePrefix
        );
    }

    /**
     * @inheritDoc
     */
    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    /**
     * @inheritDoc
     */
    public function getFieldActionRegistry(): FieldActionRegistryInterface
    {
        return $this->fieldActionRegistry;
    }

    /**
     * @inheritDoc
     */
    public function getFieldAssertionRegistry(): FieldAssertionRegistryInterface
    {
        return $this->fieldAssertionRegistry;
    }

    /**
     * @inheritDoc
     */
    public function getInteractionRegistry(): InteractionRegistryInterface
    {
        return $this->interactionRegistry;
    }

    /**
     * @inheritDoc
     */
    public function getMatcherRegistry(): MatcherRegistryInterface
    {
        return $this->matcherRegistry;
    }

    /**
     * @inheritDoc
     */
    public function getRegionAssertionRegistry(): RegionAssertionRegistryInterface
    {
        return $this->regionAssertionRegistry;
    }

    /**
     * @inheritDoc
     */
    public function getStateAssertionRegistry(): StateAssertionRegistryInterface
    {
        return $this->stateAssertionRegistry;
    }

    /**
     * @inheritDoc
     */
    public function getTypeResolver(): TypeResolverInterface
    {
        return $this->typeResolver;
    }
}
