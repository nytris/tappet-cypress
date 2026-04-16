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

namespace Tappet\Cypress\Automation;

use Tappet\Core\Action\FieldActionInterface;
use Tappet\Core\Action\InteractionInterface;
use Tappet\Core\Assertion\RegionAssertionInterface;
use Tappet\Core\Assertion\StateAssertionInterface;
use Tappet\Core\Automation\AutomationInterface;
use Tappet\Core\Automation\Field\FieldActionRegistryInterface;
use Tappet\Core\Automation\Interaction\InteractionRegistryInterface;
use Tappet\Core\Automation\Region\RegionAssertionRegistryInterface;
use Tappet\Core\Automation\State\StateAssertionRegistryInterface;
use Tappet\Core\Environment\EnvironmentInterface;
use Tappet\Core\Exception\UnresolvableTypeException;

/**
 * Class CypressAutomation.
 *
 * Represents the automation layer of a test scenario, where we integrate with Cypress.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class CypressAutomation implements AutomationInterface
{
    /**
     * @var string
     */
    private $attributePrefix;
    /**
     * @var mixed
     */
    private $cy;
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
        FieldActionRegistryInterface $fieldActionRegistry,
        InteractionRegistryInterface $interactionRegistry,
        RegionAssertionRegistryInterface $regionAssertionRegistry,
        StateAssertionRegistryInterface $stateAssertionRegistry,
        mixed $cy,
        string $attributePrefix
    ) {
        $this->attributePrefix = $attributePrefix;
        $this->cy = $cy;
        $this->fieldActionRegistry = $fieldActionRegistry;
        $this->interactionRegistry = $interactionRegistry;
        $this->regionAssertionRegistry = $regionAssertionRegistry;
        $this->stateAssertionRegistry = $stateAssertionRegistry;
    }

    /**
     * @inheritDoc
     */
    public function assertPage(string $url, EnvironmentInterface $environment): void
    {
        if ($url[0] === '/') {
            $url = $environment->getBaseUrl() . $url;
        }

        $this->cy->url()->should('eq', $url);
    }

    /**
     * Fetches the prefix used for UI automation `data-` attributes.
     */
    public function getAttributePrefix(): string
    {
        return $this->attributePrefix;
    }

    /**
     * Fetches the underlying Cypress `cy` object.
     */
    public function getCy(): mixed
    {
        return $this->cy;
    }

    /**
     * @inheritDoc
     */
    public function performFieldAction(FieldActionInterface $action): void
    {
        $attributePrefix = $this->attributePrefix;
        $automation = $this;
        $fieldHandle = $action->getFieldHandle();
        $fieldActionRegistry = $this->fieldActionRegistry;

        $this->cy->get('[data-' . $attributePrefix . '-field="' . $fieldHandle . '"]')
            ->then(function ($field) use ($action, $attributePrefix, $automation, $fieldActionRegistry, $fieldHandle) {
                $fieldType = $field->attr('data-' . $attributePrefix . '-field-type');

                if (!$fieldType) {
                    switch ($field->prop('tagName')) {
                        case 'INPUT':
                            $fieldType = strtolower($field->attr('type'));

                            if ($fieldType === 'password') {
                                $fieldType = 'text';
                            }

                            break;
                        case 'SELECT':
                            $fieldType = 'select';
                            break;
                        case 'TEXTAREA':
                            $fieldType = 'text';
                            break;
                        default:
                            throw new UnresolvableTypeException(
                                'No field type could be resolved for field with handle "' . $fieldHandle . '"'
                            );
                    }
                }

                $fieldActionRegistry->handleFieldAction($fieldType, $action, $automation);
            });
    }

    /**
     * @inheritDoc
     */
    public function performInteraction(InteractionInterface $interaction): void
    {
        $attributePrefix = $this->attributePrefix;
        $automation = $this;
        $interactionHandle = $interaction->getInteractionHandle();
        $interactionRegistry = $this->interactionRegistry;

        $this->cy->get('[data-' . $this->attributePrefix . '-interaction="' . $interactionHandle . '"]')
            ->then(function ($element) use ($attributePrefix, $automation, $interaction, $interactionRegistry, $interactionHandle) {
                $interactionType = $element->attr('data-' . $attributePrefix . '-interaction-type');

                if (!$interactionType) {
                    switch ($element->prop('tagName')) {
                        case 'A':
                            if ($element->attr('href') !== null) {
                                $interactionType = 'hyperlink';
                            } else {
                                throw new UnresolvableTypeException(
                                    'No interaction type could be resolved for interaction with handle "' . $interactionHandle . '"'
                                );
                            }
                            break;
                        case 'BUTTON':
                            $interactionType = 'button';
                            break;
                        case 'INPUT':
                            if (strtolower($element->attr('type')) === 'button') {
                                $interactionType = 'button';
                            } else {
                                throw new UnresolvableTypeException(
                                    'No interaction type could be resolved for interaction with handle "' . $interactionHandle . '"'
                                );
                            }
                            break;
                        default:
                            throw new UnresolvableTypeException(
                                'No interaction type could be resolved for interaction with handle "' . $interactionHandle . '"'
                            );
                    }
                }

                $interactionRegistry->handleInteraction($interactionType, $interaction, $automation);
            });
    }

    /**
     * @inheritDoc
     */
    public function performRegionAssertion(RegionAssertionInterface $assertion): void
    {
        $attributePrefix = $this->attributePrefix;
        $automation = $this;
        $regionHandle = $assertion->getRegionHandle();
        $regionAssertionRegistry = $this->regionAssertionRegistry;

        $this->cy->get('[data-' . $attributePrefix . '-region="' . $regionHandle . '"]')
            ->then(function ($element) use ($attributePrefix, $automation, $assertion, $regionAssertionRegistry) {
                $regionType = $element->attr('data-' . $attributePrefix . '-region-type') ?: 'text';

                $regionAssertionRegistry->handleRegionAssertion($regionType, $assertion, $automation);
            });
    }

    /**
     * @inheritDoc
     */
    public function performStateAssertion(StateAssertionInterface $assertion): void
    {
        $attributePrefix = $this->attributePrefix;
        $automation = $this;
        $stateHandle = $assertion->getStateHandle();
        $stateAssertionRegistry = $this->stateAssertionRegistry;

        $this->cy->get('[data-' . $attributePrefix . '-state="' . $stateHandle . '"]')
            ->then(function ($element) use ($attributePrefix, $automation, $assertion, $stateAssertionRegistry) {
                $stateType = $element->attr('data-' . $attributePrefix . '-state-type') ?: 'exists';

                $stateAssertionRegistry->handleStateAssertion($stateType, $assertion, $automation);
            });
    }

    /**
     * @inheritDoc
     */
    public function visitPage(string $url): void
    {
        $this->cy->visit($url);
    }
}
