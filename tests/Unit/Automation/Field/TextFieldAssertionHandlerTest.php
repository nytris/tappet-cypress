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
use Tappet\Cypress\Automation\Field\TextFieldAssertionHandler;
use Tappet\Cypress\Tests\AbstractTestCase;
use Tappet\Runner\Standard\Assertion\ExpectTextFieldValue;

/**
 * Class TextFieldAssertionHandlerTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class TextFieldAssertionHandlerTest extends AbstractTestCase
{
    /**
     * A Uniter FFI wrapper of Cypress's cy global, stubbed as an anonymous mock.
     */
    private mixed $cy;
    private CypressAutomationInterface&MockInterface $automation;
    private TextFieldAssertionHandler $handler;

    public function setUp(): void
    {
        parent::setUp();

        $this->cy = mock();
        $this->automation = mock(CypressAutomationInterface::class, [
            'getAttributePrefix' => 'ui',
            'getCy' => $this->cy,
        ]);

        $this->handler = new TextFieldAssertionHandler($this->automation);
    }

    public function testGetHandlersMapsExpectTextFieldValueClassToCallable(): void
    {
        $handlers = $this->handler->getHandlers();

        static::assertArrayHasKey(ExpectTextFieldValue::class, $handlers);
        static::assertIsCallable($handlers[ExpectTextFieldValue::class]);
    }

    public function testExpectTextFieldValueHandlerAssertsValueViaCyApi(): void
    {
        $assertion = new ExpectTextFieldValue('username', 'johndoe');
        $getChain = mock();

        $getChain->expects()
            ->should('have.value', 'johndoe')
            ->once();
        $this->cy->expects()
            ->get('[data-ui-field="username"]')
            ->once()
            ->andReturn($getChain);

        $this->handler->getHandlers()[ExpectTextFieldValue::class]($assertion);
    }

    public function testExpectTextFieldValueHandlerUsesConfiguredAttributePrefix(): void
    {
        $assertion = new ExpectTextFieldValue('username', 'johndoe');
        $automation = mock(CypressAutomationInterface::class, [
            'getCy' => $this->cy,
            'getAttributePrefix' => 'my-app',
        ]);
        $handler = new TextFieldAssertionHandler($automation);
        $getChain = mock();

        $getChain->expects()
            ->should('have.value', 'johndoe')
            ->once();
        $this->cy->expects()
            ->get('[data-my-app-field="username"]')
            ->once()
            ->andReturn($getChain);

        $handler->getHandlers()[ExpectTextFieldValue::class]($assertion);
    }
}
