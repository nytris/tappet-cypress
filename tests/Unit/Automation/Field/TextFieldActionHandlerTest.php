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
use Tappet\Core\Standard\Action\Type;
use Tappet\Cypress\Automation\CypressAutomation;
use Tappet\Cypress\Automation\Field\TextFieldActionHandler;
use Tappet\Cypress\Tests\AbstractTestCase;

/**
 * Class TextFieldActionHandlerTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class TextFieldActionHandlerTest extends AbstractTestCase
{
    // $cy is a Uniter FFI wrapper of Cypress's cy global; stub as an anonymous mock.
    private mixed $cy;
    private CypressAutomation&MockInterface $automation;
    private TextFieldActionHandler $handler;

    public function setUp(): void
    {
        parent::setUp();

        $this->cy = mock();
        $this->automation = mock(CypressAutomation::class);
        $this->automation->allows('getCy')->andReturn($this->cy);

        $this->handler = new TextFieldActionHandler();
    }

    public function testGetHandlersMapsTypeActionClassToCallable(): void
    {
        $handlers = $this->handler->getHandlers();

        static::assertArrayHasKey(Type::class, $handlers);
        static::assertIsCallable($handlers[Type::class]);
    }

    public function testTypeFieldTypesIntoFieldViaCyApi(): void
    {
        $action = new Type('username', 'hello world');

        $getChain = mock();
        $getChain->expects()
            ->clear()
            ->once()
            ->andReturn($getChain);
        $getChain->expects()
            ->type('hello world')
            ->once()
            ->andReturn($getChain);
        $this->cy->expects()
            ->get('[data-tappet-field="username"]')
            ->once()
            ->andReturn($getChain);

        $this->handler->getHandlers()[Type::class]($action, $this->automation);
    }
}
