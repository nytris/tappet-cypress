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

namespace Tappet\Cypress\Tests\Functional\Fixtures\MyTestApp\test\app\Plugin;

use Tappet\Common\Event\EventInterface;
use Tappet\Cypress\Automation\CypressAutomationInterface;
use Tappet\Cypress\Event\MatchHandlerInitEvent;
use Tappet\Cypress\Event\WindowBeforeLoadEvent;
use Tappet\Cypress\Tests\Functional\Fixtures\MyTestApp\test\app\Matcher\BadgeMatchHandler;
use Tappet\Cypress\Tests\Functional\Fixtures\MyTestApp\test\app\Transition\ModalClosedTransition;
use Tappet\Cypress\Tests\Functional\Fixtures\MyTestApp\test\app\Transition\ModalOpenTransition;
use Tappet\Suite\Plugin\PluginInterface;

/**
 * Class TestPlugin.
 *
 * Registers a MutationObserver in each AUT window to detect modal visibility changes
 * and push transition entries directly to the PHP-land TransitionLog via
 * CypressAutomationInterface::pushTransition().
 *
 * @implements PluginInterface<CypressAutomationInterface>
 */
class TestPlugin implements PluginInterface
{
    /**
     * @inheritDoc
     */
    public function getListeners(): array
    {
        return [
            MatchHandlerInitEvent::class => function (EventInterface $event): void {
                /** @var MatchHandlerInitEvent $event */
                $event->registerMatchHandler('badge', new BadgeMatchHandler($event->getAutomation()));
            },
            WindowBeforeLoadEvent::class => function (EventInterface $event, CypressAutomationInterface $automation): void {
                /** @var WindowBeforeLoadEvent $event */
                $window = $event->getWindow();

                $observer = new $window->MutationObserver(
                    function ($mutations) use ($automation): void {
                        foreach ($mutations as $mutation) {
                            if ($mutation->type !== 'attributes' || $mutation->attributeName !== 'hidden') {
                                return;
                            }

                            $el = $mutation->target;
                            $handle = $el->getAttribute('data-ui-modal');

                            if (!$handle) {
                                return;
                            }

                            $isVisible = !$el->hasAttribute('hidden');

                            $automation->pushTransition(
                                $isVisible
                                    ? new ModalOpenTransition($handle)
                                    : new ModalClosedTransition($handle)
                            );
                        }
                    }
                );

                $window->document->addEventListener(
                    'DOMContentLoaded',
                    function () use ($observer, $window): void {
                        $observer->observe($window->document->body, [
                            'subtree' => true,
                            'attributes' => true,
                            'attributeFilter' => ['hidden'],
                        ]);
                    }
                );
            },
        ];
    }
}
