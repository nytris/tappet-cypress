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
use Tappet\Cypress\Automation\Field\CheckboxFieldActionHandler;
use Tappet\Cypress\Tests\AbstractTestCase;
use Tappet\Runner\Standard\Action\Check;
use Tappet\Runner\Standard\Action\Uncheck;

/**
 * Class CheckboxFieldActionHandlerTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class CheckboxFieldActionHandlerTest extends AbstractTestCase
{
    /**
     * A Uniter FFI wrapper of Cypress's cy global, stubbed as an anonymous mock.
     */
    private mixed $cy;
    private CypressAutomationInterface&MockInterface $automation;
    private CheckboxFieldActionHandler $handler;

    public function setUp(): void
    {
        parent::setUp();

        $this->cy = mock();
        $this->automation = mock(CypressAutomationInterface::class, [
            'getAttributePrefix' => 'ui',
            'getCy' => $this->cy,
        ]);

        $this->handler = new CheckboxFieldActionHandler($this->automation);
    }

    public function testGetHandlersMapsCheckActionClassToCallable(): void
    {
        $handlers = $this->handler->getHandlers();

        static::assertArrayHasKey(Check::class, $handlers);
        static::assertIsCallable($handlers[Check::class]);
    }

    public function testGetHandlersMapsUncheckActionClassToCallable(): void
    {
        $handlers = $this->handler->getHandlers();

        static::assertArrayHasKey(Uncheck::class, $handlers);
        static::assertIsCallable($handlers[Uncheck::class]);
    }

    public function testCheckHandlerChecksCheckboxViaCyApi(): void
    {
        $action = new Check('subscribe');
        $getChain = mock();

        $getChain->expects()
            ->check()
            ->once();
        $this->cy->expects()
            ->get('[data-ui-field="subscribe"]')
            ->once()
            ->andReturn($getChain);

        $this->handler->getHandlers()[Check::class]($action);
    }

    public function testUncheckHandlerUnchecksCheckboxViaCyApi(): void
    {
        $action = new Uncheck('subscribe');
        $getChain = mock();

        $getChain->expects()
            ->uncheck()
            ->once();
        $this->cy->expects()
            ->get('[data-ui-field="subscribe"]')
            ->once()
            ->andReturn($getChain);

        $this->handler->getHandlers()[Uncheck::class]($action);
    }

    public function testCheckHandlerUsesConfiguredAttributePrefix(): void
    {
        $action = new Check('subscribe');
        $automation = mock(CypressAutomationInterface::class, [
            'getCy' => $this->cy,
            'getAttributePrefix' => 'my-app',
        ]);
        $handler = new CheckboxFieldActionHandler($automation);
        $getChain = mock();

        $getChain->expects()
            ->check()
            ->once();
        $this->cy->expects()
            ->get('[data-my-app-field="subscribe"]')
            ->once()
            ->andReturn($getChain);

        $handler->getHandlers()[Check::class]($action);
    }
}
