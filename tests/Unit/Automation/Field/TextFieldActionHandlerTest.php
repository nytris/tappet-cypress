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

namespace Tappet\Cypress\Tests\Unit\Automation\Field;

use Mockery\MockInterface;
use Tappet\Cypress\Automation\CypressAutomationInterface;
use Tappet\Cypress\Automation\Field\TextFieldActionHandler;
use Tappet\Cypress\Tests\AbstractTestCase;
use Tappet\Runner\Standard\Action\Clear;
use Tappet\Runner\Standard\Action\Type;

/**
 * Class TextFieldActionHandlerTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class TextFieldActionHandlerTest extends AbstractTestCase
{
    /**
     * A Uniter FFI wrapper of Cypress's cy global, stubbed as an anonymous mock.
     */
    private mixed $cy;
    private CypressAutomationInterface&MockInterface $automation;
    private TextFieldActionHandler $handler;

    public function setUp(): void
    {
        parent::setUp();

        $this->cy = mock();
        $this->automation = mock(CypressAutomationInterface::class, [
            'getAttributePrefix' => 'ui',
            'getCy' => $this->cy,
        ]);

        $this->handler = new TextFieldActionHandler($this->automation);
    }

    public function testGetHandlersMapsTypeActionClassToCallable(): void
    {
        $handlers = $this->handler->getHandlers();

        static::assertArrayHasKey(Type::class, $handlers);
        static::assertIsCallable($handlers[Type::class]);
    }

    public function testGetHandlersMapsClearActionClassToCallable(): void
    {
        $handlers = $this->handler->getHandlers();

        static::assertArrayHasKey(Clear::class, $handlers);
        static::assertIsCallable($handlers[Clear::class]);
    }

    public function testTypeFieldTypesIntoFieldViaCyApi(): void
    {
        $action = new Type('username', 'hello world');
        $getChain = mock();

        $getChain->expects()
            ->clear()
            ->once()
            ->andReturn($getChain);
        $getChain->expects()
            ->type('hello world')
            ->once()
            ->andReturn($getChain);
        $this->cy->expects()
            ->get('[data-ui-field="username"]')
            ->once()
            ->andReturn($getChain);

        $this->handler->getHandlers()[Type::class]($action);
    }

    public function testTypeFieldUsesConfiguredAttributePrefix(): void
    {
        $action = new Type('username', 'hello world');
        $automation = mock(CypressAutomationInterface::class, [
            'getCy' => $this->cy,
            'getAttributePrefix' => 'my-app',
        ]);
        $handler = new TextFieldActionHandler($automation);
        $getChain = mock();

        $getChain->expects()
            ->clear()
            ->once()
            ->andReturn($getChain);
        $getChain->expects()
            ->type('hello world')
            ->once()
            ->andReturn($getChain);
        $this->cy->expects()
            ->get('[data-my-app-field="username"]')
            ->once()
            ->andReturn($getChain);

        $handler->getHandlers()[Type::class]($action);
    }

    public function testClearFieldClearsFieldViaCyApi(): void
    {
        $action = new Clear('search');
        $getChain = mock();

        $getChain->expects()
            ->clear()
            ->once();
        $this->cy->expects()
            ->get('[data-ui-field="search"]')
            ->once()
            ->andReturn($getChain);

        $this->handler->getHandlers()[Clear::class]($action);
    }

    public function testClearFieldUsesConfiguredAttributePrefix(): void
    {
        $action = new Clear('search');
        $automation = mock(CypressAutomationInterface::class, [
            'getCy' => $this->cy,
            'getAttributePrefix' => 'my-app',
        ]);
        $handler = new TextFieldActionHandler($automation);
        $getChain = mock();

        $getChain->expects()
            ->clear()
            ->once();
        $this->cy->expects()
            ->get('[data-my-app-field="search"]')
            ->once()
            ->andReturn($getChain);

        $handler->getHandlers()[Clear::class]($action);
    }
}
