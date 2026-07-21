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
 * Class HyperlinkInteractionHandler.
 *
 * Handles interactions with hyperlinks.
 *
 * @implements InteractionHandlerInterface<InteractionInterface>
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class HyperlinkInteractionHandler implements InteractionHandlerInterface
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
                $this->doubleClickHyperlink($interaction);
            },
            Enact::class => function (InteractionInterface $interaction): void {
                /** @var Enact $interaction */
                $this->followHyperlink($interaction);
            },
            Hover::class => function (InteractionInterface $interaction): void {
                /** @var Hover $interaction */
                $this->hoverHyperlink($interaction);
            },
        ];
    }

    /**
     * Double-clicks the hyperlink.
     */
    public function doubleClickHyperlink(DoubleClick $interaction): void
    {
        $attributePrefix = $this->automation->getAttributePrefix();
        $cy = $this->automation->getCy();

        $cy->get('[data-' . $attributePrefix . '-interaction="' . $interaction->getInteractionHandle() . '"]')
            ->dblclick();
    }

    /**
     * Follows the hyperlink.
     */
    public function followHyperlink(Enact $interaction): void
    {
        $attributePrefix = $this->automation->getAttributePrefix();
        $cy = $this->automation->getCy();

        $cy->get('[data-' . $attributePrefix . '-interaction="' . $interaction->getInteractionHandle() . '"]')
            ->click();
    }

    /**
     * Hovers over the hyperlink.
     */
    public function hoverHyperlink(Hover $interaction): void
    {
        $attributePrefix = $this->automation->getAttributePrefix();
        $cy = $this->automation->getCy();

        $cy->get('[data-' . $attributePrefix . '-interaction="' . $interaction->getInteractionHandle() . '"]')
            ->trigger('mouseover');
    }
}
