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

namespace Tappet\Cypress\Tests\Functional\Fixtures\SymfonyTestApp\src\RemoteState;

use Tappet\Api\RemoteState\RemoteStateProviderInterface;

/**
 * Class WidgetRemoteStateProvider.
 *
 * A custom remote state provider, autoconfigured as a Symfony service and auto-tagged by
 * TappetExtension (it implements RemoteStateProviderInterface) so that
 * RegisterRemoteStateProvidersPass wires it into DelegatingRemoteStateProvider - proving a
 * remote state assertion in a GUI test scenario really does reach a provider registered
 * against the real tappet/bundle via its `/.well-known/tappet/remote-state/{handle}` route.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class WidgetRemoteStateProvider implements RemoteStateProviderInterface
{
    /**
     * @inheritDoc
     */
    public function getProviders(): array
    {
        return [
            'widget-secret-code' => function (): ?string {
                $widgetId = $_SESSION['last_loaded_widget_id'] ?? null;

                if ($widgetId === null) {
                    return null;
                }

                return $_SESSION['widgets'][$widgetId]['secret_code'] ?? null;
            },
        ];
    }
}
