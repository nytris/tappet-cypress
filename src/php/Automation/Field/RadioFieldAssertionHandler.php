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
use Tappet\Runner\Standard\Assertion\ExpectSelectedRadioOption;

/**
 * Class RadioFieldAssertionHandler.
 *
 * Handles radio button group selected-option assertions.
 *
 * @implements FieldAssertionHandlerInterface<FieldAssertionInterface>
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class RadioFieldAssertionHandler implements FieldAssertionHandlerInterface
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
            ExpectSelectedRadioOption::class => function (FieldAssertionInterface $assertion): void {
                /** @var ExpectSelectedRadioOption $assertion */
                $this->assertSelectedOption($assertion);
            },
        ];
    }

    /**
     * Asserts that the radio button with the expected value within the group is checked.
     */
    public function assertSelectedOption(ExpectSelectedRadioOption $assertion): void
    {
        $attributePrefix = $this->automation->getAttributePrefix();
        $cy = $this->automation->getCy();

        $cy->get('[data-' . $attributePrefix . '-field="' . $assertion->getFieldHandle() . '"]')
            ->filter('[value="' . $assertion->getValue() . '"]')
            ->should('be.checked');
    }
}
