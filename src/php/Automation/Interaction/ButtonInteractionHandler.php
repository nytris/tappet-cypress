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

use Tappet\Cypress\Automation\CypressAutomationInterface;
use Tappet\Runner\Action\InteractionInterface;
use Tappet\Runner\Automation\Interaction\InteractionHandlerInterface;
use Tappet\Runner\Standard\Action\DoubleClick;
use Tappet\Runner\Standard\Action\Enact;
use Tappet\Runner\Standard\Action\Hover;

/**
 * Class ButtonInteractionHandler.
 *
 * Handles interactions with buttons.
 *
 * @implements InteractionHandlerInterface<InteractionInterface>
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class ButtonInteractionHandler implements InteractionHandlerInterface
{
    public function __construct(
        private readonly CypressAutomationInterface $automation
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getHandlers(): array
    {
        return [
            DoubleClick::class => function (InteractionInterface $interaction): void {
                /** @var DoubleClick $interaction */
                $this->doubleClickButton($interaction);
            },
            Enact::class => function (InteractionInterface $interaction): void {
                /** @var Enact $interaction */
                $this->pressButton($interaction);
            },
            Hover::class => function (InteractionInterface $interaction): void {
                /** @var Hover $interaction */
                $this->hoverButton($interaction);
            },
        ];
    }

    /**
     * Double-clicks the button.
     */
    public function doubleClickButton(DoubleClick $interaction): void
    {
        $attributePrefix = $this->automation->getAttributePrefix();
        $cy = $this->automation->getCy();

        $cy->get('[data-' . $attributePrefix . '-interaction="' . $interaction->getInteractionHandle() . '"]')
            ->dblclick();
    }

    /**
     * Hovers over the button.
     */
    public function hoverButton(Hover $interaction): void
    {
        $attributePrefix = $this->automation->getAttributePrefix();
        $cy = $this->automation->getCy();

        $cy->get('[data-' . $attributePrefix . '-interaction="' . $interaction->getInteractionHandle() . '"]')
            ->trigger('mouseover');
    }

    /**
     * Presses the button.
     */
    public function pressButton(Enact $interaction): void
    {
        $attributePrefix = $this->automation->getAttributePrefix();
        $cy = $this->automation->getCy();

        $cy->get('[data-' . $attributePrefix . '-interaction="' . $interaction->getInteractionHandle() . '"]')
            ->click();
    }
}
