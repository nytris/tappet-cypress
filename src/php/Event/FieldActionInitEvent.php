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

namespace Tappet\Cypress\Event;

use Tappet\Runner\Action\FieldActionInterface;
use Tappet\Runner\Automation\Field\FieldActionHandlerInterface;

/**
 * Class FieldActionInitEvent.
 *
 * Fired during suite initialisation to allow plugins to register field action handlers.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class FieldActionInitEvent extends AbstractInitEvent
{
    /**
     * Registers a handler for the given field type.
     *
     * @param FieldActionHandlerInterface<FieldActionInterface> $handler
     */
    public function registerFieldActionHandler(string $fieldType, FieldActionHandlerInterface $handler): void
    {
        $this->getAdapter()->getFieldActionRegistry()->registerFieldActionHandler($fieldType, $handler);
    }
}
