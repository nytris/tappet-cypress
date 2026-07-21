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
use Tappet\Cypress\Automation\Field\FileFieldActionHandler;
use Tappet\Cypress\Tests\AbstractTestCase;
use Tappet\Runner\Standard\Action\Upload;

/**
 * Class FileFieldActionHandlerTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class FileFieldActionHandlerTest extends AbstractTestCase
{
    /**
     * A Uniter FFI wrapper of Cypress's cy global, stubbed as an anonymous mock.
     */
    private mixed $cy;
    private CypressAutomationInterface&MockInterface $automation;
    private FileFieldActionHandler $handler;

    public function setUp(): void
    {
        parent::setUp();

        $this->cy = mock();
        $this->automation = mock(CypressAutomationInterface::class, [
            'getAttributePrefix' => 'ui',
            'getCy' => $this->cy,
        ]);

        $this->handler = new FileFieldActionHandler($this->automation);
    }

    public function testGetHandlersMapsUploadActionClassToCallable(): void
    {
        $handlers = $this->handler->getHandlers();

        static::assertArrayHasKey(Upload::class, $handlers);
        static::assertIsCallable($handlers[Upload::class]);
    }

    public function testUploadHandlerSelectsFileViaCyApi(): void
    {
        $action = new Upload('avatar', '/tmp/photo.png');
        $getChain = mock();

        $getChain->expects()
            ->selectFile('/tmp/photo.png')
            ->once();
        $this->cy->expects()
            ->get('[data-ui-field="avatar"]')
            ->once()
            ->andReturn($getChain);

        $this->handler->getHandlers()[Upload::class]($action);
    }

    public function testUploadHandlerUsesConfiguredAttributePrefix(): void
    {
        $action = new Upload('avatar', '/tmp/photo.png');
        $automation = mock(CypressAutomationInterface::class, [
            'getCy' => $this->cy,
            'getAttributePrefix' => 'my-app',
        ]);
        $handler = new FileFieldActionHandler($automation);
        $getChain = mock();

        $getChain->expects()
            ->selectFile('/tmp/photo.png')
            ->once();
        $this->cy->expects()
            ->get('[data-my-app-field="avatar"]')
            ->once()
            ->andReturn($getChain);

        $handler->getHandlers()[Upload::class]($action);
    }
}
