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

namespace Tappet\Cypress\Tests\Unit\Automation\Interaction;

use Mockery\MockInterface;
use Tappet\Cypress\Automation\CypressAutomationInterface;
use Tappet\Cypress\Automation\Interaction\ButtonInteractionHandler;
use Tappet\Cypress\Tests\AbstractTestCase;
use Tappet\Runner\Standard\Action\DoubleClick;
use Tappet\Runner\Standard\Action\Enact;
use Tappet\Runner\Standard\Action\Hover;

/**
 * Class ButtonInteractionHandlerTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class ButtonInteractionHandlerTest extends AbstractTestCase
{
    /**
     * A Uniter FFI wrapper of Cypress's cy global, stubbed as an anonymous mock.
     */
    private mixed $cy;
    private CypressAutomationInterface&MockInterface $automation;
    private ButtonInteractionHandler $handler;

    public function setUp(): void
    {
        parent::setUp();

        $this->cy = mock();
        $this->automation = mock(CypressAutomationInterface::class, [
            'getAttributePrefix' => 'ui',
            'getCy' => $this->cy,
        ]);

        $this->handler = new ButtonInteractionHandler($this->automation);
    }

    public function testGetHandlersMapsEnactClassToCallable(): void
    {
        $handlers = $this->handler->getHandlers();

        static::assertArrayHasKey(Enact::class, $handlers);
        static::assertIsCallable($handlers[Enact::class]);
    }

    public function testGetHandlersMapsDoubleClickClassToCallable(): void
    {
        $handlers = $this->handler->getHandlers();

        static::assertArrayHasKey(DoubleClick::class, $handlers);
        static::assertIsCallable($handlers[DoubleClick::class]);
    }

    public function testGetHandlersMapsHoverClassToCallable(): void
    {
        $handlers = $this->handler->getHandlers();

        static::assertArrayHasKey(Hover::class, $handlers);
        static::assertIsCallable($handlers[Hover::class]);
    }

    public function testEnactHandlerClicksButtonViaCyApi(): void
    {
        $interaction = new Enact('publish');
        $getChain = mock();

        $getChain->expects()
            ->click()
            ->once();
        $this->cy->expects()
            ->get('[data-ui-interaction="publish"]')
            ->once()
            ->andReturn($getChain);

        $this->handler->getHandlers()[Enact::class]($interaction);
    }

    public function testEnactHandlerUsesConfiguredAttributePrefix(): void
    {
        $interaction = new Enact('publish');
        $automation = mock(CypressAutomationInterface::class, [
            'getCy' => $this->cy,
            'getAttributePrefix' => 'my-app',
        ]);
        $handler = new ButtonInteractionHandler($automation);
        $getChain = mock();

        $getChain->expects()
            ->click()
            ->once();
        $this->cy->expects()
            ->get('[data-my-app-interaction="publish"]')
            ->once()
            ->andReturn($getChain);

        $handler->getHandlers()[Enact::class]($interaction);
    }

    public function testDoubleClickHandlerDoubleClicksButtonViaCyApi(): void
    {
        $interaction = new DoubleClick('editable-cell');
        $getChain = mock();

        $getChain->expects()
            ->dblclick()
            ->once();
        $this->cy->expects()
            ->get('[data-ui-interaction="editable-cell"]')
            ->once()
            ->andReturn($getChain);

        $this->handler->getHandlers()[DoubleClick::class]($interaction);
    }

    public function testDoubleClickHandlerUsesConfiguredAttributePrefix(): void
    {
        $interaction = new DoubleClick('editable-cell');
        $automation = mock(CypressAutomationInterface::class, [
            'getCy' => $this->cy,
            'getAttributePrefix' => 'my-app',
        ]);
        $handler = new ButtonInteractionHandler($automation);
        $getChain = mock();

        $getChain->expects()
            ->dblclick()
            ->once();
        $this->cy->expects()
            ->get('[data-my-app-interaction="editable-cell"]')
            ->once()
            ->andReturn($getChain);

        $handler->getHandlers()[DoubleClick::class]($interaction);
    }

    public function testHoverHandlerTriggersMouseoverOnButtonViaCyApi(): void
    {
        $interaction = new Hover('user-menu');
        $getChain = mock();

        $getChain->expects()
            ->trigger('mouseover')
            ->once();
        $this->cy->expects()
            ->get('[data-ui-interaction="user-menu"]')
            ->once()
            ->andReturn($getChain);

        $this->handler->getHandlers()[Hover::class]($interaction);
    }

    public function testHoverHandlerUsesConfiguredAttributePrefix(): void
    {
        $interaction = new Hover('user-menu');
        $automation = mock(CypressAutomationInterface::class, [
            'getCy' => $this->cy,
            'getAttributePrefix' => 'my-app',
        ]);
        $handler = new ButtonInteractionHandler($automation);
        $getChain = mock();

        $getChain->expects()
            ->trigger('mouseover')
            ->once();
        $this->cy->expects()
            ->get('[data-my-app-interaction="user-menu"]')
            ->once()
            ->andReturn($getChain);

        $handler->getHandlers()[Hover::class]($interaction);
    }
}
