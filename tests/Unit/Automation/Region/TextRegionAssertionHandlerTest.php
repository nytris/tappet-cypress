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
use Tappet\Core\Standard\Assertion\ExpectRegionContains;
use Tappet\Core\Standard\Assertion\ExpectRegionDoesNotContain;
use Tappet\Cypress\Automation\CypressAutomation;
use Tappet\Cypress\Automation\Region\TextRegionAssertionHandler;
use Tappet\Cypress\Tests\AbstractTestCase;

/**
 * Class TextRegionAssertionHandlerTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class TextRegionAssertionHandlerTest extends AbstractTestCase
{
    // $cy is a Uniter FFI wrapper of Cypress's cy global; stub as an anonymous mock.
    private mixed $cy;
    private CypressAutomation&MockInterface $automation;
    private TextRegionAssertionHandler $handler;

    public function setUp(): void
    {
        parent::setUp();

        $this->cy = mock();
        $this->automation = mock(CypressAutomation::class, [
            'getCy' => $this->cy,
            'getAttributePrefix' => 'ui',
        ]);

        $this->handler = new TextRegionAssertionHandler();
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

        $this->handler->getHandlers()[ExpectRegionContains::class]($assertion, $this->automation);
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

        $this->handler->getHandlers()[ExpectRegionDoesNotContain::class]($assertion, $this->automation);
    }

    public function testAssertRegionContainsUsesConfiguredAttributePrefix(): void
    {
        $assertion = new ExpectRegionContains('flash-message', 'Saved successfully.');
        $automation = mock(CypressAutomation::class, [
            'getCy' => $this->cy,
            'getAttributePrefix' => 'my-app',
        ]);
        $getChain = mock();

        $getChain->expects()
            ->should('contain', 'Saved successfully.')
            ->once();
        $this->cy->expects()
            ->get('[data-my-app-region="flash-message"]')
            ->once()
            ->andReturn($getChain);

        $this->handler->getHandlers()[ExpectRegionContains::class]($assertion, $automation);
    }
}
