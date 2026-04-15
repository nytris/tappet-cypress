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
 * Class ButtonInteractionHandler.
 *
 * Handles interactions with buttons.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class ButtonInteractionHandler implements InteractionHandlerInterface
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
                $this->pressButton($interaction, $automation);
            },
        ];
    }

    /**
     * Presses the button.
     */
    public function pressButton(Enact $interaction, CypressAutomation $automation): void
    {
        $cy = $automation->getCy();

        $cy->get('[data-tappet-interaction="' . $interaction->getInteractionHandle() . '"]')->click();
    }
}
