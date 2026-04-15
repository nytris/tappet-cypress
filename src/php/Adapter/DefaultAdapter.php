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

use Tappet\Core\Automation\Field\FieldActionRegistry;
use Tappet\Core\Automation\Field\FieldActionRegistryInterface;
use Tappet\Core\Automation\Interaction\InteractionRegistry;
use Tappet\Core\Automation\Interaction\InteractionRegistryInterface;
use Tappet\Core\Automation\Region\RegionAssertionRegistry;
use Tappet\Core\Automation\Region\RegionAssertionRegistryInterface;
use Tappet\Core\Automation\State\StateAssertionRegistry;
use Tappet\Core\Automation\State\StateAssertionRegistryInterface;
use Tappet\Cypress\Automation\CypressAutomation;

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
    private $fieldActionRegistry;
    /**
     * @var InteractionRegistryInterface
     */
    private $interactionRegistry;
    /**
     * @var RegionAssertionRegistryInterface
     */
    private $regionAssertionRegistry;
    /**
     * @var StateAssertionRegistryInterface
     */
    private $stateAssertionRegistry;

    public function __construct(
        FieldActionRegistryInterface $fieldActionRegistry = new FieldActionRegistry(),
        InteractionRegistryInterface $interactionRegistry = new InteractionRegistry(),
        RegionAssertionRegistryInterface $regionAssertionRegistry = new RegionAssertionRegistry(),
        StateAssertionRegistryInterface $stateAssertionRegistry = new StateAssertionRegistry()
    ) {
        $this->fieldActionRegistry = $fieldActionRegistry;
        $this->interactionRegistry = $interactionRegistry;
        $this->regionAssertionRegistry = $regionAssertionRegistry;
        $this->stateAssertionRegistry = $stateAssertionRegistry;
    }

    /**
     * @inheritDoc
     */
    public function getAutomation(mixed $cy): CypressAutomation
    {
        return new CypressAutomation(
            $this->fieldActionRegistry,
            $this->interactionRegistry,
            $this->regionAssertionRegistry,
            $this->stateAssertionRegistry,
            $cy
        );
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
    public function getInteractionRegistry(): InteractionRegistryInterface
    {
        return $this->interactionRegistry;
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
}
