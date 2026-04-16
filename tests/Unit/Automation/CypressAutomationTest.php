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
use Tappet\Core\Action\FieldActionInterface;
use Tappet\Core\Automation\Field\FieldActionRegistryInterface;
use Tappet\Core\Automation\Interaction\InteractionRegistryInterface;
use Tappet\Core\Automation\Region\RegionAssertionRegistryInterface;
use Tappet\Core\Automation\State\StateAssertionRegistryInterface;
use Tappet\Core\Environment\EnvironmentInterface;
use Tappet\Core\Exception\UnresolvableTypeException;
use Tappet\Core\Standard\Action\Enact;
use Tappet\Core\Standard\Assertion\ExpectRegionContains;
use Tappet\Core\Standard\Assertion\ExpectRegionDoesNotContain;
use Tappet\Core\Standard\Assertion\ExpectState;
use Tappet\Cypress\Automation\CypressAutomation;
use Tappet\Cypress\Tests\AbstractTestCase;

/**
 * Class CypressAutomationTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class CypressAutomationTest extends AbstractTestCase
{
    private CypressAutomation $automation;
    // $cy is a Uniter FFI wrapper of Cypress's cy global; stub as an anonymous mock.
    private mixed $cy;
    private FieldActionRegistryInterface&MockInterface $fieldActionRegistry;
    private InteractionRegistryInterface&MockInterface $interactionRegistry;
    private RegionAssertionRegistryInterface&MockInterface $regionAssertionRegistry;
    private StateAssertionRegistryInterface&MockInterface $stateAssertionRegistry;

    public function setUp(): void
    {
        parent::setUp();

        $this->cy = mock();
        $this->fieldActionRegistry = mock(FieldActionRegistryInterface::class);
        $this->interactionRegistry = mock(InteractionRegistryInterface::class);
        $this->regionAssertionRegistry = mock(RegionAssertionRegistryInterface::class);
        $this->stateAssertionRegistry = mock(StateAssertionRegistryInterface::class);

        $this->automation = new CypressAutomation(
            $this->fieldActionRegistry,
            $this->interactionRegistry,
            $this->regionAssertionRegistry,
            $this->stateAssertionRegistry,
            $this->cy,
            'ui'
        );
    }

    public function testAssertPageCallsCyUrlThenShould(): void
    {
        $environment = mock(EnvironmentInterface::class);
        $urlChain = mock();

        $urlChain->expects()
            ->should('eq', 'https://example.com/dashboard')
            ->once();
        $this->cy->expects()
            ->url()
            ->once()
            ->andReturn($urlChain);

        $this->automation->assertPage('https://example.com/dashboard', $environment);
    }

    public function testAssertPagePrependsBaseUrlForRelativePaths(): void
    {
        $environment = mock(EnvironmentInterface::class);
        $environment->allows('getBaseUrl')->andReturn('https://example.com');
        $urlChain = mock();

        $urlChain->expects()
            ->should('eq', 'https://example.com/dashboard')
            ->once();
        $this->cy->expects()
            ->url()
            ->once()
            ->andReturn($urlChain);

        $this->automation->assertPage('/dashboard', $environment);
    }

    public function testGetAttributePrefixReturnsDefaultPrefix(): void
    {
        static::assertSame('ui', $this->automation->getAttributePrefix());
    }

    public function testGetAttributePrefixReturnsCustomPrefix(): void
    {
        $automation = new CypressAutomation(
            $this->fieldActionRegistry,
            $this->interactionRegistry,
            $this->regionAssertionRegistry,
            $this->stateAssertionRegistry,
            $this->cy,
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

    public function testPerformFieldActionCallsRegistryHandleFieldActionWithExplicitFieldType(): void
    {
        $action = mock(FieldActionInterface::class);
        $action->allows('getFieldHandle')->andReturn('username');
        $getChain = mock();
        $field = mock();
        $field->allows('attr')->with('data-ui-field-type')->andReturn('text');

        $getChain->expects()
            ->then(Mockery::on(function (callable $callback) use ($field): bool {
                $callback($field);
                return true;
            }))
            ->once();
        $this->cy->allows('get')->andReturn($getChain);

        $this->fieldActionRegistry->expects()
            ->handleFieldAction('text', $action, $this->automation)
            ->once();

        $this->automation->performFieldAction($action);
    }

    public function testPerformFieldActionCallsRegistryHandleFieldActionForInputFieldType(): void
    {
        $action = mock(FieldActionInterface::class);
        $action->allows('getFieldHandle')->andReturn('username');
        $field = mock();
        $field->allows('attr')->with('data-ui-field-type')->andReturn(null);
        $field->allows('prop')->with('tagName')->andReturn('INPUT');
        $field->allows('attr')->with('type')->andReturn('Text');
        $getChain = mock();

        $getChain->expects()
            ->then(Mockery::on(function (callable $callback) use ($field): bool {
                $callback($field);
                return true;
            }))
            ->once();
        $this->cy->allows('get')->andReturn($getChain);

        $this->fieldActionRegistry->expects()
            ->handleFieldAction('text', $action, $this->automation)
            ->once();

        $this->automation->performFieldAction($action);
    }

    public function testPerformFieldActionUsesConfiguredAttributePrefix(): void
    {
        $automation = new CypressAutomation(
            $this->fieldActionRegistry,
            $this->interactionRegistry,
            $this->regionAssertionRegistry,
            $this->stateAssertionRegistry,
            $this->cy,
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

    public function testPerformInteractionCallsRegistryHandleInteractionWithExplicitInteractionType(): void
    {
        $interaction = new Enact('submit-button');

        $element = mock();
        $element->allows('attr')->with('data-ui-interaction-type')->andReturn('click');
        $getChain = mock();

        $getChain->expects()
            ->then(Mockery::on(function (callable $callback) use ($element): bool {
                $callback($element);
                return true;
            }))
            ->once();
        $this->cy->allows('get')->andReturn($getChain);

        $this->interactionRegistry->expects()
            ->handleInteraction('click', $interaction, $this->automation)
            ->once();

        $this->automation->performInteraction($interaction);
    }

    public function testPerformInteractionResolvesButtonTypeForButtonElement(): void
    {
        $interaction = new Enact('submit-button');
        $element = mock();
        $element->allows('attr')->with('data-ui-interaction-type')->andReturn(null);
        $element->allows('prop')->with('tagName')->andReturn('BUTTON');
        $getChain = mock();

        $getChain->expects()
            ->then(Mockery::on(function (callable $callback) use ($element): bool {
                $callback($element);
                return true;
            }))
            ->once();
        $this->cy->allows('get')->andReturn($getChain);

        $this->interactionRegistry->expects()
            ->handleInteraction('button', $interaction, $this->automation)
            ->once();

        $this->automation->performInteraction($interaction);
    }

    public function testPerformInteractionResolvesHyperlinkTypeForAnchorElementWithHrefAttribute(): void
    {
        $interaction = new Enact('my-link');
        $element = mock();
        $element->allows('attr')->with('href')->andReturn('/my/url');
        $element->allows('attr')->with('data-ui-interaction-type')->andReturn(null);
        $element->allows('prop')->with('tagName')->andReturn('A');
        $getChain = mock();
        $this->cy->allows()
            ->get('[data-ui-interaction="my-link"]')
            ->andReturn($getChain);
        $getChain->allows()
            ->then(Mockery::on(function (callable $callback) use ($element): bool {
                $callback($element);
                return true;
            }));

        $this->interactionRegistry->expects()
            ->handleInteraction('hyperlink', $interaction, $this->automation)
            ->once();

        $this->automation->performInteraction($interaction);
    }

    public function testPerformInteractionThrowsWhenGivenAnchorElementWithNoHrefAttributeNorExplicitTypeAttribute(): void
    {
        $interaction = new Enact('my-link');
        $element = mock();
        $element->allows('attr')->with('href')->andReturn(null);
        $element->allows('attr')->with('data-ui-interaction-type')->andReturn(null);
        $element->allows('prop')->with('tagName')->andReturn('A');
        $getChain = mock();
        $this->cy->allows()
            ->get('[data-ui-interaction="my-link"]')
            ->andReturn($getChain);
        $getChain->allows()
            ->then(Mockery::on(function (callable $callback) use ($element): bool {
                $callback($element);
                return true;
            }));

        $this->expectException(UnresolvableTypeException::class);
        $this->expectExceptionMessage('No interaction type could be resolved for interaction with handle "my-link"');

        $this->automation->performInteraction($interaction);
    }

    public function testPerformInteractionResolvesButtonTypeForInputButtonElement(): void
    {
        $interaction = new Enact('submit-input');
        $element = mock();
        $element->allows('attr')->with('data-ui-interaction-type')->andReturn(null);
        $element->allows('prop')->with('tagName')->andReturn('INPUT');
        $element->allows('attr')->with('type')->andReturn('button');
        $getChain = mock();

        $getChain->expects()
            ->then(Mockery::on(function (callable $callback) use ($element): bool {
                $callback($element);
                return true;
            }))
            ->once();
        $this->cy->allows('get')->andReturn($getChain);

        $this->interactionRegistry->expects()
            ->handleInteraction('button', $interaction, $this->automation)
            ->once();

        $this->automation->performInteraction($interaction);
    }

    public function testPerformInteractionUsesConfiguredAttributePrefix(): void
    {
        $automation = new CypressAutomation(
            $this->fieldActionRegistry,
            $this->interactionRegistry,
            $this->regionAssertionRegistry,
            $this->stateAssertionRegistry,
            $this->cy,
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

    public function testPerformRegionAssertionCallsRegistryWithExplicitRegionType(): void
    {
        $assertion = new ExpectRegionContains('flash-message', 'Saved.');
        $element = mock();
        $element->allows('attr')->with('data-ui-region-type')->andReturn('text');
        $getChain = mock();

        $getChain->expects()
            ->then(Mockery::on(function (callable $callback) use ($element): bool {
                $callback($element);
                return true;
            }))
            ->once();
        $this->cy->allows('get')->andReturn($getChain);

        $this->regionAssertionRegistry->expects()
            ->handleRegionAssertion('text', $assertion, $this->automation)
            ->once();

        $this->automation->performRegionAssertion($assertion);
    }

    public function testPerformRegionAssertionDefaultsRegionTypeToText(): void
    {
        $assertion = new ExpectRegionDoesNotContain('flash-message', 'Error.');
        $element = mock();
        $element->allows('attr')->with('data-ui-region-type')->andReturn(null);
        $getChain = mock();

        $getChain->expects()
            ->then(Mockery::on(function (callable $callback) use ($element): bool {
                $callback($element);
                return true;
            }))
            ->once();
        $this->cy->allows('get')->andReturn($getChain);

        $this->regionAssertionRegistry->expects()
            ->handleRegionAssertion('text', $assertion, $this->automation)
            ->once();

        $this->automation->performRegionAssertion($assertion);
    }

    public function testPerformRegionAssertionUsesConfiguredAttributePrefix(): void
    {
        $automation = new CypressAutomation(
            $this->fieldActionRegistry,
            $this->interactionRegistry,
            $this->regionAssertionRegistry,
            $this->stateAssertionRegistry,
            $this->cy,
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
            ->handleStateAssertion('exists', $assertion, $this->automation)
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
            ->handleStateAssertion('exists', $assertion, $this->automation)
            ->once();

        $this->automation->performStateAssertion($assertion);
    }

    public function testPerformStateAssertionUsesConfiguredAttributePrefix(): void
    {
        $automation = new CypressAutomation(
            $this->fieldActionRegistry,
            $this->interactionRegistry,
            $this->regionAssertionRegistry,
            $this->stateAssertionRegistry,
            $this->cy,
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
}
