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

use Tappet\Cypress\Automation\Matcher\ContextInterface;
use Tappet\Cypress\Automation\Resolver\TypeResolverInterface;
use Tappet\Runner\Action\FieldActionInterface;
use Tappet\Runner\Action\InteractionInterface;
use Tappet\Runner\Assertion\FieldAssertionInterface;
use Tappet\Runner\Assertion\RegionAssertionInterface;
use Tappet\Runner\Assertion\StateAssertionInterface;
use Tappet\Runner\Automation\Field\FieldActionRegistryInterface;
use Tappet\Runner\Automation\Field\FieldAssertionRegistryInterface;
use Tappet\Runner\Automation\Interaction\InteractionRegistryInterface;
use Tappet\Runner\Automation\Region\RegionAssertionRegistryInterface;
use Tappet\Runner\Automation\State\StateAssertionRegistryInterface;
use Tappet\Runner\Exception\TransitionLogNotEmptyException;
use Tappet\Runner\Exception\TransitionWaitTimeoutException;
use Tappet\Runner\Transition\Log\TransitionLogInterface;
use Tappet\Runner\Transition\TransitionInterface;

/**
 * Class CypressAutomation.
 *
 * Represents the automation layer of a test scenario, where we integrate with Cypress.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class CypressAutomation implements CypressAutomationInterface
{
    public function __construct(
        private readonly FieldActionRegistryInterface $fieldActionRegistry,
        private readonly FieldAssertionRegistryInterface $fieldAssertionRegistry,
        private readonly InteractionRegistryInterface $interactionRegistry,
        private readonly RegionAssertionRegistryInterface $regionAssertionRegistry,
        private readonly StateAssertionRegistryInterface $stateAssertionRegistry,
        private readonly TypeResolverInterface $typeResolver,
        private readonly mixed $cy,
        private readonly TransitionLogInterface $transitionLog,
        private readonly string $attributePrefix
    ) {
    }

    /**
     * @inheritDoc
     */
    public function assertTransitionLogEmpty(): void
    {
        $transitionLog = $this->transitionLog;

        $this->cy->then(function () use ($transitionLog): void {
            $cursor = $transitionLog->getCursor();
            $count = $transitionLog->getCount();

            if ($cursor < $count) {
                $entries = $transitionLog->getEntries();
                $entry = $entries[$cursor];

                throw new TransitionLogNotEmptyException(
                    'Expected transition log to be empty at cursor ' . $cursor .
                    ' but found unconsumed entry: ' . $entry->getDescription() .
                    ".\nLog:\n" . $transitionLog->format()
                );
            }
        });
    }

    /**
     * @inheritDoc
     */
    public function checkForUnexpectedTransition(TransitionInterface $transition): void
    {
        $transitionLog = $this->transitionLog;

        $this->cy->then(function () use ($transitionLog, $transition): void {
            $cursor = $transitionLog->getCursor();
            $count = $transitionLog->getCount();

            if ($cursor >= $count) {
                return; // No pending transition, as expected.
            }

            $transitionLog->consumeTransition($transition);
        });
    }

    /**
     * @inheritDoc
     */
    public function getAttributePrefix(): string
    {
        return $this->attributePrefix;
    }

    /**
     * @inheritDoc
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
        $fieldHandle = $action->getFieldHandle();
        $fieldActionRegistry = $this->fieldActionRegistry;
        $typeResolver = $this->typeResolver;

        $this->cy->get('[data-' . $attributePrefix . '-field="' . $fieldHandle . '"]')
            ->then(function ($field) use (
                $action,
                $attributePrefix,
                $fieldActionRegistry,
                $fieldHandle,
                $typeResolver
            ) {
                $fieldType = $typeResolver->resolveFieldType($field, $fieldHandle, $attributePrefix);

                $fieldActionRegistry->handleFieldAction($fieldType, $action);
            });
    }

    /**
     * @inheritDoc
     */
    public function performFieldAssertion(FieldAssertionInterface $assertion): void
    {
        $attributePrefix = $this->attributePrefix;
        $fieldHandle = $assertion->getFieldHandle();
        $fieldAssertionRegistry = $this->fieldAssertionRegistry;
        $typeResolver = $this->typeResolver;

        $this->cy->get('[data-' . $attributePrefix . '-field="' . $fieldHandle . '"]')
            ->then(function ($field) use (
                $assertion,
                $attributePrefix,
                $fieldAssertionRegistry,
                $fieldHandle,
                $typeResolver
            ) {
                $fieldType = $typeResolver->resolveFieldType($field, $fieldHandle, $attributePrefix);

                $fieldAssertionRegistry->handleFieldAssertion($fieldType, $assertion);
            });
    }

    /**
     * @inheritDoc
     */
    public function performInteraction(InteractionInterface $interaction): void
    {
        $attributePrefix = $this->attributePrefix;
        $interactionHandle = $interaction->getInteractionHandle();
        $interactionRegistry = $this->interactionRegistry;
        $typeResolver = $this->typeResolver;

        $this->cy->get('[data-' . $this->attributePrefix . '-interaction="' . $interactionHandle . '"]')
            ->then(function ($element) use (
                $attributePrefix,
                $interaction,
                $interactionRegistry,
                $interactionHandle,
                $typeResolver
            ) {
                $interactionType = $typeResolver->resolveInteractionType($element, $interactionHandle, $attributePrefix);

                $interactionRegistry->handleInteraction($interactionType, $interaction);
            });
    }

    /**
     * @inheritDoc
     */
    public function performRegionAssertion(RegionAssertionInterface $assertion): void
    {
        $attributePrefix = $this->attributePrefix;
        $regionHandle = $assertion->getRegionHandle();
        $regionAssertionRegistry = $this->regionAssertionRegistry;
        $typeResolver = $this->typeResolver;

        $this->cy->get('[data-' . $attributePrefix . '-region="' . $regionHandle . '"]')
            ->then(function ($element) use ($attributePrefix, $assertion, $regionAssertionRegistry, $typeResolver) {
                $regionType = $typeResolver->resolveRegionType($element, $attributePrefix);

                $regionAssertionRegistry->handleRegionAssertion($regionType, $assertion);
            });
    }

    /**
     * @inheritDoc
     */
    public function performStateAssertion(StateAssertionInterface $assertion): void
    {
        $attributePrefix = $this->attributePrefix;
        $stateHandle = $assertion->getStateHandle();
        $stateAssertionRegistry = $this->stateAssertionRegistry;

        $this->cy->get('[data-' . $attributePrefix . '-state="' . $stateHandle . '"]')
            ->then(function ($element) use ($attributePrefix, $assertion, $stateAssertionRegistry) {
                $stateType = $element->attr('data-' . $attributePrefix . '-state-type') ?: 'exists';

                $stateAssertionRegistry->handleStateAssertion($stateType, $assertion);
            });
    }

    /**
     * @inheritDoc
     */
    public function pushTransition(TransitionInterface $transition): void
    {
        $this->transitionLog->pushTransition($transition);
    }

    /**
     * @inheritDoc
     */
    public function resolve(ContextInterface $context): mixed
    {
        return $context->getTarget();
    }

    /**
     * @inheritDoc
     */
    public function visitPage(string $url): void
    {
        $this->cy->visit($url);
    }

    /**
     * @inheritDoc
     */
    public function waitForTransition(TransitionInterface $transition): void
    {
        $transitionLog = $this->transitionLog;

        // Use cy.wrap(...) so that the check is retried until it doesn't throw (or Cypress' command timeout is reached).
        $this->cy->wrap(null)->should(function () use ($transitionLog, $transition): void {
            $cursor = $transitionLog->getCursor();
            $count = $transitionLog->getCount();

            if ($cursor >= $count) {
                throw new TransitionWaitTimeoutException(
                    'Waiting for ' . $transition->getDescription() . ' but log is empty at cursor ' . $cursor .
                    ".\nLog:\n" . $transitionLog->format()
                );
            }

            $transitionLog->consumeTransition($transition);
        });
    }
}
