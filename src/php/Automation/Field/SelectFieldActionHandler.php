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
use Tappet\Runner\Standard\Action\Select;

/**
 * Class SelectFieldActionHandler.
 *
 * Handles actions on select dropdown fields.
 *
 * @implements FieldActionHandlerInterface<FieldActionInterface>
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class SelectFieldActionHandler implements FieldActionHandlerInterface
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
            Select::class => function (FieldActionInterface $action): void {
                /** @var Select $action */
                $this->selectOption($action);
            },
        ];
    }

    /**
     * Selects the specified option of the select dropdown field.
     */
    public function selectOption(Select $action): void
    {
        $attributePrefix = $this->automation->getAttributePrefix();
        $cy = $this->automation->getCy();

        $cy->get('[data-' . $attributePrefix . '-field="' . $action->getFieldHandle() . '"]')
            ->select($action->getOptionValue());
    }
}
