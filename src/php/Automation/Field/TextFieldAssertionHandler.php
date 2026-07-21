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
use Tappet\Runner\Assertion\FieldAssertionInterface;
use Tappet\Runner\Automation\Field\FieldAssertionHandlerInterface;
use Tappet\Runner\Standard\Assertion\ExpectTextFieldValue;

/**
 * Class TextFieldAssertionHandler.
 *
 * Handles text field value assertions.
 *
 * @implements FieldAssertionHandlerInterface<FieldAssertionInterface>
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class TextFieldAssertionHandler implements FieldAssertionHandlerInterface
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
            ExpectTextFieldValue::class => function (FieldAssertionInterface $assertion): void {
                /** @var ExpectTextFieldValue $assertion */
                $this->assertFieldValue($assertion);
            },
        ];
    }

    /**
     * Asserts that the field has the expected value.
     */
    public function assertFieldValue(ExpectTextFieldValue $assertion): void
    {
        $attributePrefix = $this->automation->getAttributePrefix();
        $cy = $this->automation->getCy();

        $cy->get('[data-' . $attributePrefix . '-field="' . $assertion->getFieldHandle() . '"]')
            ->should('have.value', $assertion->getValue());
    }
}
