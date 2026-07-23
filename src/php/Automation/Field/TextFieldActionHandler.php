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
use Tappet\Runner\Standard\Action\Clear;
use Tappet\Runner\Standard\Action\Type;

/**
 * Class TextFieldActionHandler.
 *
 * Handles actions on text fields.
 *
 * @implements FieldActionHandlerInterface<FieldActionInterface>
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class TextFieldActionHandler implements FieldActionHandlerInterface
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
            Clear::class => function (FieldActionInterface $action): void {
                /** @var Clear $action */
                $this->clearField($action);
            },
            Type::class => function (FieldActionInterface $action): void {
                /** @var Type $action */
                $this->typeField($action);
            },
        ];
    }

    /**
     * Clears the text field without typing anything new.
     */
    public function clearField(Clear $action): void
    {
        $attributePrefix = $this->automation->getAttributePrefix();
        $cy = $this->automation->getCy();

        $cy->get('[data-' . $attributePrefix . '-field="' . $action->getFieldHandle() . '"]')
            ->clear();
    }

    /**
     * Types the specified text into the text field.
     */
    public function typeField(Type $action): void
    {
        $attributePrefix = $this->automation->getAttributePrefix();
        $cy = $this->automation->getCy();

        $cy->get('[data-' . $attributePrefix . '-field="' . $action->getFieldHandle() . '"]')
            ->clear()
            ->type($action->getText());
    }
}
