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

use Tappet\Runner\Assertion\FieldAssertionInterface;
use Tappet\Runner\Automation\Field\FieldAssertionHandlerInterface;

/**
 * Class FieldAssertionInitEvent.
 *
 * Fired during suite initialisation to allow plugins to register field assertion handlers.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class FieldAssertionInitEvent extends AbstractInitEvent
{
    /**
     * Registers a handler for assertions of the given field type.
     *
     * @param FieldAssertionHandlerInterface<FieldAssertionInterface> $handler
     */
    public function registerFieldAssertionHandler(string $fieldType, FieldAssertionHandlerInterface $handler): void
    {
        $this->getAdapter()->getFieldAssertionRegistry()->registerFieldAssertionHandler($fieldType, $handler);
    }
}
