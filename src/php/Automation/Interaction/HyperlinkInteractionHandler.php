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

namespace Tappet\Cypress\Automation\Interaction;

use Tappet\Core\Action\InteractionInterface;
use Tappet\Core\Automation\AutomationInterface;
use Tappet\Core\Automation\Interaction\InteractionHandlerInterface;
use Tappet\Core\Standard\Action\Enact;
use Tappet\Cypress\Automation\CypressAutomation;

/**
 * Class HyperlinkInteractionHandler.
 *
 * Handles interactions with hyperlinks.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class HyperlinkInteractionHandler implements InteractionHandlerInterface
{
    /**
     * @inheritDoc
     */
    public function getHandlers(): array
    {
        return [
            Enact::class => function (InteractionInterface $interaction, AutomationInterface $automation): void {
                /** @var Enact $interaction */
                /** @var CypressAutomation $automation */
                $this->followHyperlink($interaction, $automation);
            },
        ];
    }

    /**
     * Follows the hyperlink.
     */
    public function followHyperlink(Enact $interaction, CypressAutomation $automation): void
    {
        $cy = $automation->getCy();

        $cy->get('[data-tappet-interaction="' . $interaction->getInteractionHandle() . '"]')->click();
    }
}
