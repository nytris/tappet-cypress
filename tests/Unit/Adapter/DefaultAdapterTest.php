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

namespace Tappet\Cypress\Tests\Unit\Adapter;

use Mockery;
use Mockery\MockInterface;
use Tappet\Common\Event\EventDispatcher;
use Tappet\Common\Event\EventDispatcherInterface;
use Tappet\Cypress\Adapter\DefaultAdapter;
use Tappet\Cypress\Automation\CypressAutomation;
use Tappet\Cypress\Automation\Matcher\ContextInterface;
use Tappet\Cypress\Automation\Resolver\TypeResolver;
use Tappet\Cypress\Automation\Resolver\TypeResolverInterface;
use Tappet\Cypress\Tests\AbstractTestCase;
use Tappet\Runner\Action\FieldActionInterface;
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
 * Class DefaultAdapterTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class DefaultAdapterTest extends AbstractTestCase
{
    private DefaultAdapter $adapter;

    public function setUp(): void
    {
        parent::setUp();

        $this->adapter = new DefaultAdapter();
    }

    public function testConstructorDefaultsFieldActionRegistryToConcreteImplementation(): void
    {
        static::assertInstanceOf(FieldActionRegistry::class, $this->adapter->getFieldActionRegistry());
    }

    public function testConstructorDefaultsFieldAssertionRegistryToConcreteImplementation(): void
    {
        static::assertInstanceOf(FieldAssertionRegistry::class, $this->adapter->getFieldAssertionRegistry());
    }

    public function testConstructorDefaultsInteractionRegistryToConcreteImplementation(): void
    {
        static::assertInstanceOf(InteractionRegistry::class, $this->adapter->getInteractionRegistry());
    }

    public function testConstructorDefaultsMatcherRegistryToConcreteImplementation(): void
    {
        static::assertInstanceOf(MatcherRegistry::class, $this->adapter->getMatcherRegistry());
    }

    public function testConstructorDefaultsRegionAssertionRegistryToConcreteImplementation(): void
    {
        static::assertInstanceOf(RegionAssertionRegistry::class, $this->adapter->getRegionAssertionRegistry());
    }

    public function testConstructorDefaultsStateAssertionRegistryToConcreteImplementation(): void
    {
        static::assertInstanceOf(StateAssertionRegistry::class, $this->adapter->getStateAssertionRegistry());
    }

    public function testConstructorDefaultsTypeResolverToConcreteImplementation(): void
    {
        static::assertInstanceOf(TypeResolver::class, $this->adapter->getTypeResolver());
    }

    public function testConstructorDefaultsEventDispatcherToConcreteImplementation(): void
    {
        static::assertInstanceOf(EventDispatcher::class, $this->adapter->getEventDispatcher());
    }

    public function testConstructorUsesGivenFieldActionRegistry(): void
    {
        $fieldActionRegistry = mock(FieldActionRegistryInterface::class);

        $adapter = new DefaultAdapter(fieldActionRegistry: $fieldActionRegistry);

        static::assertSame($fieldActionRegistry, $adapter->getFieldActionRegistry());
    }

    public function testConstructorUsesGivenFieldAssertionRegistry(): void
    {
        $fieldAssertionRegistry = mock(FieldAssertionRegistryInterface::class);

        $adapter = new DefaultAdapter(fieldAssertionRegistry: $fieldAssertionRegistry);

        static::assertSame($fieldAssertionRegistry, $adapter->getFieldAssertionRegistry());
    }

    public function testConstructorUsesGivenInteractionRegistry(): void
    {
        $interactionRegistry = mock(InteractionRegistryInterface::class);

        $adapter = new DefaultAdapter(interactionRegistry: $interactionRegistry);

        static::assertSame($interactionRegistry, $adapter->getInteractionRegistry());
    }

    public function testConstructorUsesGivenMatcherRegistry(): void
    {
        /** @var MatcherRegistryInterface<ContextInterface>&MockInterface $matcherRegistry */
        $matcherRegistry = mock(MatcherRegistryInterface::class);

        $adapter = new DefaultAdapter(matcherRegistry: $matcherRegistry);

        static::assertSame($matcherRegistry, $adapter->getMatcherRegistry());
    }

    public function testConstructorUsesGivenRegionAssertionRegistry(): void
    {
        $regionAssertionRegistry = mock(RegionAssertionRegistryInterface::class);

        $adapter = new DefaultAdapter(regionAssertionRegistry: $regionAssertionRegistry);

        static::assertSame($regionAssertionRegistry, $adapter->getRegionAssertionRegistry());
    }

    public function testConstructorUsesGivenStateAssertionRegistry(): void
    {
        $stateAssertionRegistry = mock(StateAssertionRegistryInterface::class);

        $adapter = new DefaultAdapter(stateAssertionRegistry: $stateAssertionRegistry);

        static::assertSame($stateAssertionRegistry, $adapter->getStateAssertionRegistry());
    }

    public function testConstructorUsesGivenTypeResolver(): void
    {
        $typeResolver = mock(TypeResolverInterface::class);

        $adapter = new DefaultAdapter(typeResolver: $typeResolver);

        static::assertSame($typeResolver, $adapter->getTypeResolver());
    }

    public function testConstructorUsesGivenEventDispatcher(): void
    {
        $eventDispatcher = mock(EventDispatcherInterface::class);

        $adapter = new DefaultAdapter(eventDispatcher: $eventDispatcher);

        static::assertSame($eventDispatcher, $adapter->getEventDispatcher());
    }

    public function testGetAutomationReturnsACypressAutomationInstance(): void
    {
        $cy = mock();
        $transitionLog = mock(TransitionLogInterface::class);

        $automation = $this->adapter->getAutomation($cy, $transitionLog);

        static::assertInstanceOf(CypressAutomation::class, $automation);
    }

    public function testGetAutomationPassesTheGivenCyThrough(): void
    {
        $cy = mock();
        $transitionLog = mock(TransitionLogInterface::class);

        $automation = $this->adapter->getAutomation($cy, $transitionLog);

        static::assertSame($cy, $automation->getCy());
    }

    public function testGetAutomationDefaultsAttributePrefixToUi(): void
    {
        $cy = mock();
        $transitionLog = mock(TransitionLogInterface::class);

        $automation = $this->adapter->getAutomation($cy, $transitionLog);

        static::assertSame('ui', $automation->getAttributePrefix());
    }

    public function testGetAutomationUsesTheConfiguredAttributePrefix(): void
    {
        $adapter = new DefaultAdapter(attributePrefix: 'my-app');
        $cy = mock();
        $transitionLog = mock(TransitionLogInterface::class);

        $automation = $adapter->getAutomation($cy, $transitionLog);

        static::assertSame('my-app', $automation->getAttributePrefix());
    }

    public function testGetAutomationWiresTheGivenFieldActionRegistryThrough(): void
    {
        $fieldActionRegistry = mock(FieldActionRegistryInterface::class);
        $adapter = new DefaultAdapter(fieldActionRegistry: $fieldActionRegistry);
        $cy = mock();
        $transitionLog = mock(TransitionLogInterface::class);
        $action = mock(FieldActionInterface::class);
        $action->allows('getFieldHandle')->andReturn('username');
        $field = mock();
        $field->allows('attr')->with('data-ui-field-type')->andReturn('text');
        $getChain = mock();
        $getChain->allows()->then(Mockery::on(function (callable $callback) use ($field): bool {
            $callback($field);
            return true;
        }));
        $cy->allows('get')->andReturn($getChain);

        $fieldActionRegistry->expects()
            ->handleFieldAction(Mockery::any(), $action)
            ->once();

        $automation = $adapter->getAutomation($cy, $transitionLog);

        $automation->performFieldAction($action);
    }
}