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

namespace Tappet\Cypress\Tests\Unit\Automation\Region;

use Mockery\MockInterface;
use Tappet\Cypress\Automation\CypressAutomationInterface;
use Tappet\Cypress\Automation\Region\TextRegionAssertionHandler;
use Tappet\Cypress\Tests\AbstractTestCase;
use Tappet\Runner\Standard\Assertion\ExpectRegionContains;
use Tappet\Runner\Standard\Assertion\ExpectRegionDoesNotContain;

/**
 * Class TextRegionAssertionHandlerTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class TextRegionAssertionHandlerTest extends AbstractTestCase
{
    /**
     * A Uniter FFI wrapper of Cypress's cy global, stubbed as an anonymous mock.
     */
    private mixed $cy;
    private CypressAutomationInterface&MockInterface $automation;
    private TextRegionAssertionHandler $handler;

    public function setUp(): void
    {
        parent::setUp();

        $this->cy = mock();
        $this->automation = mock(CypressAutomationInterface::class, [
            'getCy' => $this->cy,
            'getAttributePrefix' => 'ui',
        ]);

        $this->handler = new TextRegionAssertionHandler($this->automation);
    }

    public function testGetHandlersMapsExpectRegionContainsClassToCallable(): void
    {
        $handlers = $this->handler->getHandlers();

        static::assertArrayHasKey(ExpectRegionContains::class, $handlers);
        static::assertIsCallable($handlers[ExpectRegionContains::class]);
    }

    public function testGetHandlersMapsExpectRegionDoesNotContainClassToCallable(): void
    {
        $handlers = $this->handler->getHandlers();

        static::assertArrayHasKey(ExpectRegionDoesNotContain::class, $handlers);
        static::assertIsCallable($handlers[ExpectRegionDoesNotContain::class]);
    }

    public function testAssertRegionContainsAssertsCorrectTextViaCyApi(): void
    {
        $assertion = new ExpectRegionContains('flash-message', 'Saved successfully.');
        $getChain = mock();

        $getChain->expects()
            ->should('contain', 'Saved successfully.')
            ->once();
        $this->cy->expects()
            ->get('[data-ui-region="flash-message"]')
            ->once()
            ->andReturn($getChain);

        $this->handler->getHandlers()[ExpectRegionContains::class]($assertion);
    }

    public function testAssertRegionDoesNotContainNegativelyAssertsCorrectTextViaCyApi(): void
    {
        $assertion = new ExpectRegionDoesNotContain('flash-message', 'Something went wrong.');
        $getChain = mock();

        $getChain->expects()
            ->should('not.contain', 'Something went wrong.')
            ->once();
        $this->cy->expects()
            ->get('[data-ui-region="flash-message"]')
            ->once()
            ->andReturn($getChain);

        $this->handler->getHandlers()[ExpectRegionDoesNotContain::class]($assertion);
    }

    public function testAssertRegionContainsUsesConfiguredAttributePrefix(): void
    {
        $assertion = new ExpectRegionContains('flash-message', 'Saved successfully.');
        $automation = mock(CypressAutomationInterface::class, [
            'getCy' => $this->cy,
            'getAttributePrefix' => 'my-app',
        ]);
        $handler = new TextRegionAssertionHandler($automation);
        $getChain = mock();

        $getChain->expects()
            ->should('contain', 'Saved successfully.')
            ->once();
        $this->cy->expects()
            ->get('[data-my-app-region="flash-message"]')
            ->once()
            ->andReturn($getChain);

        $handler->getHandlers()[ExpectRegionContains::class]($assertion);
    }
}
