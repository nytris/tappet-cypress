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

namespace Tappet\Cypress\Automation\Field;

use Tappet\Cypress\Automation\CypressAutomationInterface;
use Tappet\Runner\Action\FieldActionInterface;
use Tappet\Runner\Automation\Field\FieldActionHandlerInterface;
use Tappet\Runner\Standard\Action\ChooseRadioOption;

/**
 * Class RadioFieldActionHandler.
 *
 * Handles actions on radio button group fields.
 *
 * @implements FieldActionHandlerInterface<FieldActionInterface>
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class RadioFieldActionHandler implements FieldActionHandlerInterface
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
            ChooseRadioOption::class => function (FieldActionInterface $action): void {
                /** @var ChooseRadioOption $action */
                $this->chooseRadioOption($action);
            },
        ];
    }

    /**
     * Checks the radio button with the given value within the radio group.
     */
    public function chooseRadioOption(ChooseRadioOption $action): void
    {
        $attributePrefix = $this->automation->getAttributePrefix();
        $cy = $this->automation->getCy();

        $cy->get('[data-' . $attributePrefix . '-field="' . $action->getFieldHandle() . '"]')
            ->check($action->getOptionValue());
    }
}
