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
use Tappet\Cypress\Automation\Field\SelectFieldAssertionHandler;
use Tappet\Cypress\Tests\AbstractTestCase;
use Tappet\Runner\Standard\Assertion\ExpectSelectedOption;

/**
 * Class SelectFieldAssertionHandlerTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class SelectFieldAssertionHandlerTest extends AbstractTestCase
{
    /**
     * A Uniter FFI wrapper of Cypress's cy global, stubbed as an anonymous mock.
     */
    private mixed $cy;
    private CypressAutomationInterface&MockInterface $automation;
    private SelectFieldAssertionHandler $handler;

    public function setUp(): void
    {
        parent::setUp();

        $this->cy = mock();
        $this->automation = mock(CypressAutomationInterface::class, [
            'getAttributePrefix' => 'ui',
            'getCy' => $this->cy,
        ]);

        $this->handler = new SelectFieldAssertionHandler($this->automation);
    }

    public function testGetHandlersMapsExpectSelectedOptionClassToCallable(): void
    {
        $handlers = $this->handler->getHandlers();

        static::assertArrayHasKey(ExpectSelectedOption::class, $handlers);
        static::assertIsCallable($handlers[ExpectSelectedOption::class]);
    }

    public function testExpectSelectedOptionHandlerAssertsValueViaCyApi(): void
    {
        $assertion = new ExpectSelectedOption('role', 'admin');
        $getChain = mock();

        $getChain->expects()
            ->should('have.value', 'admin')
            ->once();
        $this->cy->expects()
            ->get('[data-ui-field="role"]')
            ->once()
            ->andReturn($getChain);

        $this->handler->getHandlers()[ExpectSelectedOption::class]($assertion);
    }

    public function testExpectSelectedOptionHandlerUsesConfiguredAttributePrefix(): void
    {
        $assertion = new ExpectSelectedOption('role', 'admin');
        $automation = mock(CypressAutomationInterface::class, [
            'getCy' => $this->cy,
            'getAttributePrefix' => 'my-app',
        ]);
        $handler = new SelectFieldAssertionHandler($automation);
        $getChain = mock();

        $getChain->expects()
            ->should('have.value', 'admin')
            ->once();
        $this->cy->expects()
            ->get('[data-my-app-field="role"]')
            ->once()
            ->andReturn($getChain);

        $handler->getHandlers()[ExpectSelectedOption::class]($assertion);
    }
}
