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

namespace Tappet\Cypress\Tests\Unit\Automation\Matcher;

use Mockery\MockInterface;
use Tappet\Cypress\Automation\CypressAutomationInterface;
use Tappet\Cypress\Automation\Matcher\Context;
use Tappet\Cypress\Automation\Matcher\TextMatchHandler;
use Tappet\Cypress\Tests\AbstractTestCase;
use Tappet\Runner\Standard\Matcher\ExactText;
use Tappet\Runner\Standard\Matcher\Text;

/**
 * Class TextMatchHandlerTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class TextMatchHandlerTest extends AbstractTestCase
{
    private CypressAutomationInterface&MockInterface $automation;
    private Context $context;
    private TextMatchHandler $handler;
    /**
     * A Uniter FFI wrapper of a Cypress chainable, stubbed as an anonymous mock.
     */
    private mixed $target;

    public function setUp(): void
    {
        parent::setUp();

        $this->automation = mock(CypressAutomationInterface::class);
        $this->target = mock();
        $this->context = new Context($this->target);
        $this->automation->allows('resolve')->with($this->context)->andReturn($this->target);

        $this->handler = new TextMatchHandler($this->automation);
    }

    public function testGetHandlersMapsTextClassToCallable(): void
    {
        $handlers = $this->handler->getHandlers();

        static::assertArrayHasKey(Text::class, $handlers);
        static::assertIsCallable($handlers[Text::class]);
    }

    public function testGetHandlersMapsExactTextClassToCallable(): void
    {
        $handlers = $this->handler->getHandlers();

        static::assertArrayHasKey(ExactText::class, $handlers);
        static::assertIsCallable($handlers[ExactText::class]);
    }

    public function testMatchTextCallsCyContainsWithMatcherText(): void
    {
        $matcher = new Text('some text');

        $this->target->expects()
            ->contains('some text')
            ->once();

        $this->handler->getHandlers()[Text::class]($matcher, $this->context);
    }

    public function testMatchExactTextAssertsExactTextViaCyApi(): void
    {
        $matcher = new ExactText('some text');

        $this->target->expects()
            ->should('have.text', 'some text')
            ->once();

        $this->handler->getHandlers()[ExactText::class]($matcher, $this->context);
    }
}
