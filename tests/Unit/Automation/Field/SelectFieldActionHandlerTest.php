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
use Tappet\Cypress\Automation\Field\SelectFieldActionHandler;
use Tappet\Cypress\Tests\AbstractTestCase;
use Tappet\Runner\Standard\Action\Select;

/**
 * Class SelectFieldActionHandlerTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class SelectFieldActionHandlerTest extends AbstractTestCase
{
    /**
     * A Uniter FFI wrapper of Cypress's cy global, stubbed as an anonymous mock.
     */
    private mixed $cy;
    private CypressAutomationInterface&MockInterface $automation;
    private SelectFieldActionHandler $handler;

    public function setUp(): void
    {
        parent::setUp();

        $this->cy = mock();
        $this->automation = mock(CypressAutomationInterface::class, [
            'getAttributePrefix' => 'ui',
            'getCy' => $this->cy,
        ]);

        $this->handler = new SelectFieldActionHandler($this->automation);
    }

    public function testGetHandlersMapsSelectActionClassToCallable(): void
    {
        $handlers = $this->handler->getHandlers();

        static::assertArrayHasKey(Select::class, $handlers);
        static::assertIsCallable($handlers[Select::class]);
    }

    public function testSelectSelectsOptionOnFieldViaCyApi(): void
    {
        $action = new Select('country', 'gb');
        $getChain = mock();

        $getChain->expects()
            ->select('gb')
            ->once()
            ->andReturn($getChain);
        $this->cy->expects()
            ->get('[data-ui-field="country"]')
            ->once()
            ->andReturn($getChain);

        $this->handler->getHandlers()[Select::class]($action);
    }

    public function testSelectUsesConfiguredAttributePrefix(): void
    {
        $action = new Select('country', 'gb');
        $automation = mock(CypressAutomationInterface::class, [
            'getCy' => $this->cy,
            'getAttributePrefix' => 'my-app',
        ]);
        $handler = new SelectFieldActionHandler($automation);
        $getChain = mock();

        $getChain->expects()
            ->select('gb')
            ->once()
            ->andReturn($getChain);
        $this->cy->expects()
            ->get('[data-my-app-field="country"]')
            ->once()
            ->andReturn($getChain);

        $handler->getHandlers()[Select::class]($action);
    }
}
