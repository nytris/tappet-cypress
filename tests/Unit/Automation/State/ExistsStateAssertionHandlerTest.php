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

namespace Tappet\Cypress\Tests\Unit\Automation\State;

use Mockery\MockInterface;
use Tappet\Core\Standard\Assertion\ExpectState;
use Tappet\Cypress\Automation\CypressAutomation;
use Tappet\Cypress\Automation\State\ExistsStateAssertionHandler;
use Tappet\Cypress\Tests\AbstractTestCase;

/**
 * Class ExistsStateAssertionHandlerTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class ExistsStateAssertionHandlerTest extends AbstractTestCase
{
    // $cy is a Uniter FFI wrapper of Cypress's cy global; stub as an anonymous mock.
    private mixed $cy;
    private CypressAutomation&MockInterface $automation;
    private ExistsStateAssertionHandler $handler;

    public function setUp(): void
    {
        parent::setUp();

        $this->cy = mock();
        $this->automation = mock(CypressAutomation::class);
        $this->automation->allows('getCy')->andReturn($this->cy);

        $this->handler = new ExistsStateAssertionHandler();
    }

    public function testGetHandlersMapsExpectStateClassToCallable(): void
    {
        $handlers = $this->handler->getHandlers();

        static::assertArrayHasKey(ExpectState::class, $handlers);
        static::assertIsCallable($handlers[ExpectState::class]);
    }

    public function testAssertStateExistsAssertsExistenceOfSelectorViaCyApi(): void
    {
        $assertion = new ExpectState('import-pending');

        $getChain = mock();
        $getChain->expects()
            ->should('exist')
            ->once();
        $this->cy->expects()
            ->get('[data-tappet-state="import-pending"]')
            ->once()
            ->andReturn($getChain);

        $this->handler->getHandlers()[ExpectState::class]($assertion, $this->automation);
    }
}
