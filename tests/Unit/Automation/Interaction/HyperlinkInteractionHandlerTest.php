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

namespace Tappet\Cypress\Tests\Unit\Automation\Interaction;

use Mockery\MockInterface;
use Tappet\Core\Standard\Action\Enact;
use Tappet\Cypress\Automation\CypressAutomation;
use Tappet\Cypress\Automation\Interaction\HyperlinkInteractionHandler;
use Tappet\Cypress\Tests\AbstractTestCase;

/**
 * Class HyperlinkInteractionHandlerTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class HyperlinkInteractionHandlerTest extends AbstractTestCase
{
    // $cy is a Uniter FFI wrapper of Cypress's cy global; stub as an anonymous mock.
    private mixed $cy;
    private CypressAutomation&MockInterface $automation;
    private HyperlinkInteractionHandler $handler;

    public function setUp(): void
    {
        parent::setUp();

        $this->cy = mock();
        $this->automation = mock(CypressAutomation::class);
        $this->automation->allows('getCy')->andReturn($this->cy);

        $this->handler = new HyperlinkInteractionHandler();
    }

    public function testGetHandlersMapsPerformInteractionClassToCallable(): void
    {
        $handlers = $this->handler->getHandlers();

        static::assertArrayHasKey(Enact::class, $handlers);
        static::assertIsCallable($handlers[Enact::class]);
    }

    public function testEnactHandlerClicksHyperlinkViaCyApi(): void
    {
        $interaction = new Enact('my-link');

        $getChain = mock();
        $getChain->expects()
            ->click()
            ->once();
        $this->cy->expects()
            ->get('[data-tappet-interaction="my-link"]')
            ->once()
            ->andReturn($getChain);

        $this->handler->getHandlers()[Enact::class]($interaction, $this->automation);
    }
}
