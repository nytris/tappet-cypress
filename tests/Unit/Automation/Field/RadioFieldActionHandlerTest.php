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
use Tappet\Cypress\Automation\Field\RadioFieldActionHandler;
use Tappet\Cypress\Tests\AbstractTestCase;
use Tappet\Runner\Standard\Action\ChooseRadioOption;

/**
 * Class RadioFieldActionHandlerTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class RadioFieldActionHandlerTest extends AbstractTestCase
{
    /**
     * A Uniter FFI wrapper of Cypress's cy global, stubbed as an anonymous mock.
     */
    private mixed $cy;
    private CypressAutomationInterface&MockInterface $automation;
    private RadioFieldActionHandler $handler;

    public function setUp(): void
    {
        parent::setUp();

        $this->cy = mock();
        $this->automation = mock(CypressAutomationInterface::class, [
            'getAttributePrefix' => 'ui',
            'getCy' => $this->cy,
        ]);

        $this->handler = new RadioFieldActionHandler($this->automation);
    }

    public function testGetHandlersMapsChooseRadioOptionActionClassToCallable(): void
    {
        $handlers = $this->handler->getHandlers();

        static::assertArrayHasKey(ChooseRadioOption::class, $handlers);
        static::assertIsCallable($handlers[ChooseRadioOption::class]);
    }

    public function testChooseRadioOptionHandlerChecksRadioButtonViaCyApi(): void
    {
        $action = new ChooseRadioOption('payment-method', 'credit-card');
        $getChain = mock();

        $getChain->expects()
            ->check('credit-card')
            ->once();
        $this->cy->expects()
            ->get('[data-ui-field="payment-method"]')
            ->once()
            ->andReturn($getChain);

        $this->handler->getHandlers()[ChooseRadioOption::class]($action);
    }

    public function testChooseRadioOptionHandlerUsesConfiguredAttributePrefix(): void
    {
        $action = new ChooseRadioOption('payment-method', 'credit-card');
        $automation = mock(CypressAutomationInterface::class, [
            'getCy' => $this->cy,
            'getAttributePrefix' => 'my-app',
        ]);
        $handler = new RadioFieldActionHandler($automation);
        $getChain = mock();

        $getChain->expects()
            ->check('credit-card')
            ->once();
        $this->cy->expects()
            ->get('[data-my-app-field="payment-method"]')
            ->once()
            ->andReturn($getChain);

        $handler->getHandlers()[ChooseRadioOption::class]($action);
    }
}
