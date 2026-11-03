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
use Tappet\Cypress\Automation\Matcher\ValueMatchHandler;
use Tappet\Cypress\Tests\AbstractTestCase;
use Tappet\Runner\Exception\AssertionFailedException;
use Tappet\Runner\Standard\Matcher\ExactText;
use Tappet\Runner\Standard\Matcher\Text;

/**
 * Class ValueMatchHandlerTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class ValueMatchHandlerTest extends AbstractTestCase
{
    private CypressAutomationInterface&MockInterface $automation;
    private Context $context;
    private ValueMatchHandler $handler;
    /**
     * The raw PHP value fetched via a remote state assertion, wrapped by the context.
     */
    private mixed $target;

    public function setUp(): void
    {
        parent::setUp();

        $this->automation = mock(CypressAutomationInterface::class);
        $this->target = 'some text';
        $this->context = new Context($this->target);
        $this->automation->allows()
            ->resolve($this->context)
            ->andReturn($this->target)
            ->byDefault();

        $this->handler = new ValueMatchHandler($this->automation);
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

    public function testMatchTextDoesNotThrowWhenValueContainsMatcherText(): void
    {
        $matcher = new Text('some');

        $this->expectNotToPerformAssertions();

        $this->handler->getHandlers()[Text::class]($matcher, $this->context);
    }

    public function testMatchTextThrowsAssertionFailedExceptionWhenValueDoesNotContainMatcherText(): void
    {
        $matcher = new Text('other');

        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Expected value "some text" to contain "other"');

        $this->handler->getHandlers()[Text::class]($matcher, $this->context);
    }

    public function testMatchExactTextDoesNotThrowWhenValueEqualsMatcherTextExactly(): void
    {
        $matcher = new ExactText('some text');

        $this->expectNotToPerformAssertions();

        $this->handler->getHandlers()[ExactText::class]($matcher, $this->context);
    }

    public function testMatchExactTextThrowsAssertionFailedExceptionWhenValueDoesNotEqualMatcherTextExactly(): void
    {
        $matcher = new ExactText('some');

        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage(
            'Expected value to equal "some" exactly but got "some text"'
        );

        $this->handler->getHandlers()[ExactText::class]($matcher, $this->context);
    }
}
