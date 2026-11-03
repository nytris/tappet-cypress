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

namespace Tappet\Cypress\Tests\Functional\Fixtures\SymfonyTestApp\test\app\Plugin;

use Tappet\Common\Event\EventInterface;
use Tappet\Cypress\Automation\CypressAutomationInterface;
use Tappet\Cypress\Event\RemoteStateAssertionInitEvent;
use Tappet\Cypress\Tests\Functional\Fixtures\SymfonyTestApp\test\app\RemoteState\WidgetRemoteStateAssertionHandler;
use Tappet\Suite\Plugin\PluginInterface;

/**
 * Class WidgetTestPlugin.
 *
 * Registers the custom remote state assertion handler for this test app.
 *
 * @implements PluginInterface<CypressAutomationInterface>
 */
class WidgetTestPlugin implements PluginInterface
{
    /**
     * @inheritDoc
     */
    public function getListeners(): array
    {
        return [
            RemoteStateAssertionInitEvent::class => function (EventInterface $event): void {
                /** @var RemoteStateAssertionInitEvent $event */
                $event->registerRemoteStateAssertionHandler(
                    new WidgetRemoteStateAssertionHandler($event->getAutomation(), $event->getMatcherRegistry())
                );
            },
        ];
    }
}
