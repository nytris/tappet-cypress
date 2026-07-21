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

namespace Tappet\Cypress\Tests\Unit\Automation;

use Mockery;
use Mockery\MockInterface;
use Tappet\Cypress\Automation\CypressAutomation;
use Tappet\Cypress\Automation\Matcher\Context;
use Tappet\Cypress\Automation\Resolver\TypeResolverInterface;
use Tappet\Cypress\Tests\AbstractTestCase;
use Tappet\Runner\Action\FieldActionInterface;
use Tappet\Runner\Assertion\FieldAssertionInterface;
use Tappet\Runner\Automation\Field\FieldActionRegistryInterface;
use Tappet\Runner\Automation\Field\FieldAssertionRegistryInterface;
use Tappet\Runner\Automation\Interaction\InteractionRegistryInterface;
use Tappet\Runner\Automation\Region\RegionAssertionRegistryInterface;
use Tappet\Runner\Automation\State\StateAssertionRegistryInterface;
use Tappet\Runner\Exception\TransitionLogNotEmptyException;
use Tappet\Runner\Exception\TransitionWaitTimeoutException;
use Tappet\Runner\Exception\UnexpectedTransitionException;
use Tappet\Runner\Standard\Action\Enact;
use Tappet\Runner\Standard\Assertion\ExpectRegionContains;
use Tappet\Runner\Standard\Assertion\ExpectState;
use Tappet\Runner\Transition\Log\TransitionLogInterface;
use Tappet\Runner\Transition\NavigationTransition;
use Tappet\Runner\Transition\TransitionInterface;

/**
 * Class CypressAutomationTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class CypressAutomationTest extends AbstractTestCase
{
    private CypressAutomation $automation;
    /**
     * A Uniter FFI wrapper of Cypress's cy global, stubbed as an anonymous mock.
     */
    private mixed $cy;
    /**
     * @var FieldActionRegistryInterface&MockInterface
     */
    private FieldActionRegistryInterface&MockInterface $fieldActionRegistry;
    /**
     * @var FieldAssertionRegistryInterface&MockInterface
     */
    private FieldAssertionRegistryInterface&MockInterface $fieldAssertionRegistry;
    /**
     * @var InteractionRegistryInterface&MockInterface
     */
    private InteractionRegistryInterface&MockInterface $interactionRegistry;
    /**
     * @var RegionAssertionRegistryInterface&MockInterface
     */
    private RegionAssertionRegistryInterface&MockInterface $regionAssertionRegistry;
    /**
     * @var StateAssertionRegistryInterface&MockInterface
     */
    private StateAssertionRegistryInterface&MockInterface $stateAssertionRegistry;
    private TransitionLogInterface&MockInterface $transitionLog;
    private TypeResolverInterface&MockInterface $typeResolver;

    public function setUp(): void
    {
        parent::setUp();

        $this->cy = mock();
        $this->fieldActionRegistry = mock(FieldActionRegistryInterface::class);
        $this->fieldAssertionRegistry = mock(FieldAssertionRegistryInterface::class);
        $this->interactionRegistry = mock(InteractionRegistryInterface::class);
        $this->regionAssertionRegistry = mock(RegionAssertionRegistryInterface::class);
        $this->stateAssertionRegistry = mock(StateAssertionRegistryInterface::class);
        $this->transitionLog = mock(TransitionLogInterface::class);
        $this->typeResolver = mock(TypeResolverInterface::class);

        $this->automation = new CypressAutomation(
            $this->fieldActionRegistry,
            $this->fieldAssertionRegistry,
            $this->interactionRegistry,
            $this->regionAssertionRegistry,
            $this->stateAssertionRegistry,
            $this->typeResolver,
            $this->cy,
            $this->transitionLog,
            'ui'
        );
    }

    public function testAssertTransitionLogEmptyEnqueuesCyThen(): void
    {
        $this->cy->expects()
            ->then(Mockery::on(fn ($fn) => is_callable($fn)))
            ->once();

        $this->automation->assertTransitionLogEmpty();
    }

    public function testAssertTransitionLogEmptyPassesWhenLogIsEmpty(): void
    {
        $this->transitionLog->allows('getCursor')->andReturn(0);
        $this->transitionLog->allows('getCount')->andReturn(0);
        $captured = null;
        $this->cy->allows()->then(Mockery::on(function (callable $fn) use (&$captured): bool {
            $captured = $fn;
            return true;
        }));

        $this->automation->assertTransitionLogEmpty();

        // Should not throw.
        ($captured)();
        $this->addToAssertionCount(1);
    }

    public function testAssertTransitionLogEmptyThrowsWhenUnconsumedEntryExists(): void
    {
        $this->transitionLog->allows('getCursor')->andReturn(0);
        $this->transitionLog->allows('getCount')->andReturn(1);
        $entry = mock(TransitionInterface::class);
        $entry->allows('getDescription')->andReturn('modal "my-modal" opening');
        $this->transitionLog->allows('getEntries')->andReturn([$entry]);
        $this->transitionLog->allows('format')->andReturn('> [0] modal "my-modal" opening');
        $captured = null;
        $this->cy->allows()->then(Mockery::on(function (callable $fn) use (&$captured): bool {
            $captured = $fn;
            return true;
        }));

        $this->automation->assertTransitionLogEmpty();

        $this->expectException(TransitionLogNotEmptyException::class);
        $this->expectExceptionMessage('Expected transition log to be empty');
        $this->expectExceptionMessage('modal "my-modal" opening');

        ($captured)();
    }

    public function testCheckForUnexpectedTransitionEnqueuesCyThen(): void
    {
        $transition = new NavigationTransition('/page');

        $this->cy->expects()
            ->then(Mockery::on(fn ($fn) => is_callable($fn)))
            ->once();

        $this->automation->checkForUnexpectedTransition($transition);
    }

    public function testCheckForUnexpectedTransitionPassesWhenLogHasNoUnconsumedEntries(): void
    {
        $transition = new NavigationTransition('/page');
        $this->transitionLog->allows('getCursor')->andReturn(0);
        $this->transitionLog->allows('getCount')->andReturn(0);
        $captured = null;
        $this->cy->allows()->then(Mockery::on(function (callable $fn) use (&$captured): bool {
            $captured = $fn;
            return true;
        }));

        $this->automation->checkForUnexpectedTransition($transition);

        // Should not throw.
        ($captured)();
        $this->addToAssertionCount(1);
    }

    public function testCheckForUnexpectedTransitionConsumesMatchingEntry(): void
    {
        $transition = new NavigationTransition('/page');
        $this->transitionLog->allows('getCursor')->andReturn(0);
        $this->transitionLog->allows('getCount')->andReturn(1);
        $this->transitionLog->expects()->consumeTransition($transition)->once();
        $captured = null;
        $this->cy->allows()->then(Mockery::on(function (callable $fn) use (&$captured): bool {
            $captured = $fn;
            return true;
        }));

        $this->automation->checkForUnexpectedTransition($transition);

        ($captured)();
    }

    public function testCheckForUnexpectedTransitionThrowsOnUnexpectedEntry(): void
    {
        $transition = new NavigationTransition('/page');
        $this->transitionLog->allows('getCursor')->andReturn(0);
        $this->transitionLog->allows('getCount')->andReturn(1);
        $this->transitionLog->allows('consumeTransition')->andThrow(
            new UnexpectedTransitionException('Unexpected modal "my-modal" opening transition at cursor 0.')
        );
        $captured = null;
        $this->cy->allows()->then(Mockery::on(function (callable $fn) use (&$captured): bool {
            $captured = $fn;
            return true;
        }));

        $this->automation->checkForUnexpectedTransition($transition);

        $this->expectException(UnexpectedTransitionException::class);
        $this->expectExceptionMessage('Unexpected modal "my-modal" opening transition');

        ($captured)();
    }

    public function testWaitForTransitionEnqueuesCyWrapShould(): void
    {
        $transition = new NavigationTransition('/page');

        $wrapChain = mock();
        $wrapChain->expects()
            ->should(Mockery::on(fn ($fn) => is_callable($fn)))
            ->once();
        $this->cy->expects()
            ->wrap(null)
            ->once()
            ->andReturn($wrapChain);

        $this->automation->waitForTransition($transition);
    }

    public function testWaitForTransitionConsumesMatchingEntry(): void
    {
        $transition = new NavigationTransition('/page');
        $this->transitionLog->allows('getCursor')->andReturn(0);
        $this->transitionLog->allows('getCount')->andReturn(1);
        $this->transitionLog->expects()->consumeTransition($transition)->once();
        $captured = null;
        $wrapChain = mock();
        $wrapChain->allows()->should(Mockery::on(function (callable $fn) use (&$captured): bool {
            $captured = $fn;
            return true;
        }));
        $this->cy->allows()->wrap(null)->andReturn($wrapChain);

        $this->automation->waitForTransition($transition);

        ($captured)();
    }

    public function testWaitForTransitionThrowsWhenLogIsEmpty(): void
    {
        $transition = new NavigationTransition('/page');
        $this->transitionLog->allows('getCursor')->andReturn(0);
        $this->transitionLog->allows('getCount')->andReturn(0);
        $this->transitionLog->allows('format')->andReturn('(empty)');
        $captured = null;
        $wrapChain = mock();
        $wrapChain->allows()->should(Mockery::on(function (callable $fn) use (&$captured): bool {
            $captured = $fn;
            return true;
        }));
        $this->cy->allows()->wrap(null)->andReturn($wrapChain);

        $this->automation->waitForTransition($transition);

        $this->expectException(TransitionWaitTimeoutException::class);
        $this->expectExceptionMessage('Waiting for navigation to "/page"');

        ($captured)();
    }

    public function testWaitForTransitionThrowsOnUnexpectedEntry(): void
    {
        $transition = new NavigationTransition('/page');
        $this->transitionLog->allows('getCursor')->andReturn(0);
        $this->transitionLog->allows('getCount')->andReturn(1);
        $this->transitionLog->allows('consumeTransition')->andThrow(
            new TransitionWaitTimeoutException('Unexpected modal "my-modal" opening transition at cursor 0.')
        );
        $captured = null;
        $wrapChain = mock();
        $wrapChain->allows()->should(Mockery::on(function (callable $fn) use (&$captured): bool {
            $captured = $fn;
            return true;
        }));
        $this->cy->allows()->wrap(null)->andReturn($wrapChain);

        $this->automation->waitForTransition($transition);

        $this->expectException(TransitionWaitTimeoutException::class);
        $this->expectExceptionMessage('Unexpected modal "my-modal" opening transition');

        ($captured)();
    }

    public function testPushTransitionDelegatesToTransitionLog(): void
    {
        $transition = new NavigationTransition('/my-page');

        $this->transitionLog->expects()
            ->pushTransition($transition)
            ->once();

        $this->automation->pushTransition($transition);
    }

    public function testResolveReturnsTheContextsUnderlyingTarget(): void
    {
        $target = mock();
        $context = new Context($target);

        static::assertSame($target, $this->automation->resolve($context));
    }

    public function testGetAttributePrefixReturnsDefaultPrefix(): void
    {
        static::assertSame('ui', $this->automation->getAttributePrefix());
    }

    public function testGetAttributePrefixReturnsCustomPrefix(): void
    {
        $automation = new CypressAutomation(
            $this->fieldActionRegistry,
            $this->fieldAssertionRegistry,
            $this->interactionRegistry,
            $this->regionAssertionRegistry,
            $this->stateAssertionRegistry,
            $this->typeResolver,
            $this->cy,
            $this->transitionLog,
            'my-app'
        );

        static::assertSame('my-app', $automation->getAttributePrefix());
    }

    public function testGetCyReturnsTheInnerCypressApiObject(): void
    {
        static::assertSame($this->cy, $this->automation->getCy());
    }

    public function testPerformFieldActionCallsCyGetWithFieldHandleSelector(): void
    {
        $action = mock(FieldActionInterface::class);
        $action->allows('getFieldHandle')->andReturn('username');
        $getChain = mock();
        $getChain->allows('then');

        $this->cy->expects()
            ->get('[data-ui-field="username"]')
            ->once()
            ->andReturn($getChain);

        $this->automation->performFieldAction($action);
    }

    public function testPerformFieldActionCallsRegistryHandleFieldActionWithTypeFromTypeResolver(): void
    {
        $action = mock(FieldActionInterface::class);
        $action->allows('getFieldHandle')->andReturn('username');
        $getChain = mock();
        $field = mock();

        $getChain->expects()
            ->then(Mockery::on(function (callable $callback) use ($field): bool {
                $callback($field);
                return true;
            }))
            ->once();
        $this->cy->allows('get')->andReturn($getChain);
        $this->typeResolver->expects()
            ->resolveFieldType($field, 'username', 'ui')
            ->once()
            ->andReturn('text');
        $this->fieldActionRegistry->expects()
            ->handleFieldAction('text', $action)
            ->once();

        $this->automation->performFieldAction($action);
    }

    public function testPerformFieldActionUsesConfiguredAttributePrefix(): void
    {
        $automation = new CypressAutomation(
            $this->fieldActionRegistry,
            $this->fieldAssertionRegistry,
            $this->interactionRegistry,
            $this->regionAssertionRegistry,
            $this->stateAssertionRegistry,
            $this->typeResolver,
            $this->cy,
            $this->transitionLog,
            'my-app'
        );
        $action = mock(FieldActionInterface::class);
        $action->allows('getFieldHandle')->andReturn('username');
        $getChain = mock();
        $getChain->allows('then');

        $this->cy->expects()
            ->get('[data-my-app-field="username"]')
            ->once()
            ->andReturn($getChain);

        $automation->performFieldAction($action);
    }

    public function testPerformInteractionCallsCyGetWithInteractionHandleSelector(): void
    {
        $interaction = new Enact('submit-button');
        $getChain = mock();
        $getChain->allows('then');

        $this->cy->expects()
            ->get('[data-ui-interaction="submit-button"]')
            ->once()
            ->andReturn($getChain);

        $this->automation->performInteraction($interaction);
    }

    public function testPerformInteractionCallsRegistryHandleInteractionWithTypeFromTypeResolver(): void
    {
        $interaction = new Enact('submit-button');
        $element = mock();
        $getChain = mock();

        $getChain->expects()
            ->then(Mockery::on(function (callable $callback) use ($element): bool {
                $callback($element);
                return true;
            }))
            ->once();
        $this->cy->allows('get')->andReturn($getChain);
        $this->typeResolver->expects()
            ->resolveInteractionType($element, 'submit-button', 'ui')
            ->once()
            ->andReturn('button');
        $this->interactionRegistry->expects()
            ->handleInteraction('button', $interaction)
            ->once();

        $this->automation->performInteraction($interaction);
    }

    public function testPerformInteractionUsesConfiguredAttributePrefix(): void
    {
        $automation = new CypressAutomation(
            $this->fieldActionRegistry,
            $this->fieldAssertionRegistry,
            $this->interactionRegistry,
            $this->regionAssertionRegistry,
            $this->stateAssertionRegistry,
            $this->typeResolver,
            $this->cy,
            $this->transitionLog,
            'my-app'
        );
        $interaction = new Enact('submit-button');
        $getChain = mock();
        $getChain->allows('then');

        $this->cy->expects()
            ->get('[data-my-app-interaction="submit-button"]')
            ->once()
            ->andReturn($getChain);

        $automation->performInteraction($interaction);
    }

    public function testPerformRegionAssertionCallsCyGetWithRegionHandleSelector(): void
    {
        $assertion = new ExpectRegionContains('flash-message', 'Saved.');
        $getChain = mock();
        $getChain->allows('then');

        $this->cy->expects()
            ->get('[data-ui-region="flash-message"]')
            ->once()
            ->andReturn($getChain);

        $this->automation->performRegionAssertion($assertion);
    }

    public function testPerformRegionAssertionCallsRegistryWithTypeFromTypeResolver(): void
    {
        $assertion = new ExpectRegionContains('flash-message', 'Saved.');
        $element = mock();
        $getChain = mock();

        $getChain->expects()
            ->then(Mockery::on(function (callable $callback) use ($element): bool {
                $callback($element);
                return true;
            }))
            ->once();
        $this->cy->allows('get')->andReturn($getChain);
        $this->typeResolver->expects()
            ->resolveRegionType($element, 'ui')
            ->once()
            ->andReturn('text');
        $this->regionAssertionRegistry->expects()
            ->handleRegionAssertion('text', $assertion)
            ->once();

        $this->automation->performRegionAssertion($assertion);
    }

    public function testPerformRegionAssertionUsesConfiguredAttributePrefix(): void
    {
        $automation = new CypressAutomation(
            $this->fieldActionRegistry,
            $this->fieldAssertionRegistry,
            $this->interactionRegistry,
            $this->regionAssertionRegistry,
            $this->stateAssertionRegistry,
            $this->typeResolver,
            $this->cy,
            $this->transitionLog,
            'my-app'
        );
        $assertion = new ExpectRegionContains('flash-message', 'Saved.');
        $getChain = mock();
        $getChain->allows('then');

        $this->cy->expects()
            ->get('[data-my-app-region="flash-message"]')
            ->once()
            ->andReturn($getChain);

        $automation->performRegionAssertion($assertion);
    }

    public function testPerformStateAssertionCallsCyGetWithStateHandleSelector(): void
    {
        $assertion = new ExpectState('loading-spinner');
        $getChain = mock();
        $getChain->allows('then');

        $this->cy->expects()
            ->get('[data-ui-state="loading-spinner"]')
            ->once()
            ->andReturn($getChain);

        $this->automation->performStateAssertion($assertion);
    }

    public function testPerformStateAssertionCallsRegistryWithExplicitStateType(): void
    {
        $assertion = new ExpectState('loading-spinner');
        $element = mock();
        $element->allows('attr')->with('data-ui-state-type')->andReturn('exists');
        $getChain = mock();

        $getChain->expects()
            ->then(Mockery::on(function (callable $callback) use ($element): bool {
                $callback($element);
                return true;
            }))
            ->once();
        $this->cy->allows('get')->andReturn($getChain);
        $this->stateAssertionRegistry->expects()
            ->handleStateAssertion('exists', $assertion)
            ->once();

        $this->automation->performStateAssertion($assertion);
    }

    public function testPerformStateAssertionDefaultsStateTypeToExists(): void
    {
        $assertion = new ExpectState('loading-spinner');
        $element = mock();
        $element->allows('attr')->with('data-ui-state-type')->andReturn(null);
        $getChain = mock();

        $getChain->expects()
            ->then(Mockery::on(function (callable $callback) use ($element): bool {
                $callback($element);
                return true;
            }))
            ->once();
        $this->cy->allows('get')->andReturn($getChain);
        $this->stateAssertionRegistry->expects()
            ->handleStateAssertion('exists', $assertion)
            ->once();

        $this->automation->performStateAssertion($assertion);
    }

    public function testPerformStateAssertionUsesConfiguredAttributePrefix(): void
    {
        $automation = new CypressAutomation(
            $this->fieldActionRegistry,
            $this->fieldAssertionRegistry,
            $this->interactionRegistry,
            $this->regionAssertionRegistry,
            $this->stateAssertionRegistry,
            $this->typeResolver,
            $this->cy,
            $this->transitionLog,
            'my-app'
        );
        $assertion = new ExpectState('loading-spinner');
        $getChain = mock();
        $getChain->allows('then');

        $this->cy->expects()
            ->get('[data-my-app-state="loading-spinner"]')
            ->once()
            ->andReturn($getChain);

        $automation->performStateAssertion($assertion);
    }

    public function testVisitPageCallsCyVisit(): void
    {
        $this->cy->expects()
            ->visit('https://example.com/login')
            ->once();

        $this->automation->visitPage('https://example.com/login');
    }

    public function testPerformFieldAssertionCallsCyGetWithFieldHandleSelector(): void
    {
        $assertion = mock(FieldAssertionInterface::class);
        $assertion->allows('getFieldHandle')->andReturn('username');
        $getChain = mock();
        $getChain->allows('then');

        $this->cy->expects()
            ->get('[data-ui-field="username"]')
            ->once()
            ->andReturn($getChain);

        $this->automation->performFieldAssertion($assertion);
    }

    public function testPerformFieldAssertionCallsRegistryHandleFieldAssertionWithTypeFromTypeResolver(): void
    {
        $assertion = mock(FieldAssertionInterface::class);
        $assertion->allows('getFieldHandle')->andReturn('username');
        $field = mock();
        $getChain = mock();

        $getChain->expects()
            ->then(Mockery::on(function (callable $callback) use ($field): bool {
                $callback($field);
                return true;
            }))
            ->once();
        $this->cy->allows('get')->andReturn($getChain);
        $this->typeResolver->expects()
            ->resolveFieldType($field, 'username', 'ui')
            ->once()
            ->andReturn('email');
        $this->fieldAssertionRegistry->expects()
            ->handleFieldAssertion('email', $assertion)
            ->once();

        $this->automation->performFieldAssertion($assertion);
    }

    public function testPerformFieldAssertionUsesConfiguredAttributePrefix(): void
    {
        $automation = new CypressAutomation(
            $this->fieldActionRegistry,
            $this->fieldAssertionRegistry,
            $this->interactionRegistry,
            $this->regionAssertionRegistry,
            $this->stateAssertionRegistry,
            $this->typeResolver,
            $this->cy,
            $this->transitionLog,
            'my-app'
        );
        $assertion = mock(FieldAssertionInterface::class);
        $assertion->allows('getFieldHandle')->andReturn('username');
        $getChain = mock();
        $getChain->allows('then');

        $this->cy->expects()
            ->get('[data-my-app-field="username"]')
            ->once()
            ->andReturn($getChain);

        $automation->performFieldAssertion($assertion);
    }
}
