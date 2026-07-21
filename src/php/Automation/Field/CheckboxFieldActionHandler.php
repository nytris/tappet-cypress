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
use Tappet\Runner\Standard\Action\Check;
use Tappet\Runner\Standard\Action\Uncheck;

/**
 * Class CheckboxFieldActionHandler.
 *
 * Handles actions on checkbox fields.
 *
 * @implements FieldActionHandlerInterface<FieldActionInterface>
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class CheckboxFieldActionHandler implements FieldActionHandlerInterface
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
            Check::class => function (FieldActionInterface $action): void {
                /** @var Check $action */
                $this->checkCheckbox($action);
            },
            Uncheck::class => function (FieldActionInterface $action): void {
                /** @var Uncheck $action */
                $this->uncheckCheckbox($action);
            },
        ];
    }

    /**
     * Checks the checkbox field.
     */
    public function checkCheckbox(Check $action): void
    {
        $attributePrefix = $this->automation->getAttributePrefix();
        $cy = $this->automation->getCy();

        $cy->get('[data-' . $attributePrefix . '-field="' . $action->getFieldHandle() . '"]')->check();
    }

    /**
     * Unchecks the checkbox field.
     */
    public function uncheckCheckbox(Uncheck $action): void
    {
        $attributePrefix = $this->automation->getAttributePrefix();
        $cy = $this->automation->getCy();

        $cy->get('[data-' . $attributePrefix . '-field="' . $action->getFieldHandle() . '"]')->uncheck();
    }
}
