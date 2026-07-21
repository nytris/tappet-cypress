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
use Tappet\Cypress\Automation\Field\RadioFieldAssertionHandler;
use Tappet\Cypress\Tests\AbstractTestCase;
use Tappet\Runner\Standard\Assertion\ExpectSelectedRadioOption;

/**
 * Class RadioFieldAssertionHandlerTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class RadioFieldAssertionHandlerTest extends AbstractTestCase
{
    /**
     * A Uniter FFI wrapper of Cypress's cy global, stubbed as an anonymous mock.
     */
    private mixed $cy;
    private CypressAutomationInterface&MockInterface $automation;
    private RadioFieldAssertionHandler $handler;

    public function setUp(): void
    {
        parent::setUp();

        $this->cy = mock();
        $this->automation = mock(CypressAutomationInterface::class, [
            'getAttributePrefix' => 'ui',
            'getCy' => $this->cy,
        ]);

        $this->handler = new RadioFieldAssertionHandler($this->automation);
    }

    public function testGetHandlersMapsExpectSelectedRadioOptionClassToCallable(): void
    {
        $handlers = $this->handler->getHandlers();

        static::assertArrayHasKey(ExpectSelectedRadioOption::class, $handlers);
        static::assertIsCallable($handlers[ExpectSelectedRadioOption::class]);
    }

    public function testExpectSelectedRadioOptionHandlerAssertsCheckedViaCyApi(): void
    {
        $assertion = new ExpectSelectedRadioOption('status', 'active');
        $getChain = mock();
        $filterChain = mock();

        $filterChain->expects()
            ->should('be.checked')
            ->once();
        $getChain->expects()
            ->filter('[value="active"]')
            ->once()
            ->andReturn($filterChain);
        $this->cy->expects()
            ->get('[data-ui-field="status"]')
            ->once()
            ->andReturn($getChain);

        $this->handler->getHandlers()[ExpectSelectedRadioOption::class]($assertion);
    }

    public function testExpectSelectedRadioOptionHandlerUsesConfiguredAttributePrefix(): void
    {
        $assertion = new ExpectSelectedRadioOption('status', 'active');
        $automation = mock(CypressAutomationInterface::class, [
            'getCy' => $this->cy,
            'getAttributePrefix' => 'my-app',
        ]);
        $handler = new RadioFieldAssertionHandler($automation);
        $getChain = mock();
        $filterChain = mock();

        $filterChain->expects()
            ->should('be.checked')
            ->once();
        $getChain->expects()
            ->filter('[value="active"]')
            ->once()
            ->andReturn($filterChain);
        $this->cy->expects()
            ->get('[data-my-app-field="status"]')
            ->once()
            ->andReturn($getChain);

        $handler->getHandlers()[ExpectSelectedRadioOption::class]($assertion);
    }
}
