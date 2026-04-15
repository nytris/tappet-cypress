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

use Tappet\Core\Action\FieldActionInterface;
use Tappet\Core\Automation\AutomationInterface;
use Tappet\Core\Automation\Field\FieldActionHandlerInterface;
use Tappet\Core\Standard\Action\Type;
use Tappet\Cypress\Automation\CypressAutomation;

/**
 * Class TextFieldActionHandler.
 *
 * Handles actions on text fields.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class TextFieldActionHandler implements FieldActionHandlerInterface
{
    /**
     * @inheritDoc
     */
    public function getHandlers(): array
    {
        return [
            Type::class => function (FieldActionInterface $action, AutomationInterface $automation): void {
                /** @var Type $action */
                /** @var CypressAutomation $automation */
                $this->typeField($action, $automation);
            },
        ];
    }

    /**
     * Types the specified text into the text field.
     */
    public function typeField(Type $action, CypressAutomation $automation): void
    {
        $cy = $automation->getCy();

        $cy->get('[data-tappet-field="' . $action->getFieldHandle() . '"]')->clear()->type($action->getText());
    }
}
