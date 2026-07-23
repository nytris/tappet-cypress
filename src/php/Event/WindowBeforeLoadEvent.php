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

use Tappet\Common\Event\EventInterface;

/**
 * Class WindowBeforeLoadEvent.
 *
 * Fired before each AUT window load, allowing plugins to perform setup
 * such as attaching MutationObservers for custom transition detection.
 *
 * The $window property is a Uniter FFI wrapper of the browser's Window object,
 * so JS methods/properties (e.g. $window->eval(...)) can be called from PHP.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class WindowBeforeLoadEvent implements EventInterface
{
    public function __construct(
        private readonly mixed $window
    ) {
    }

    /**
     * Fetches the AUT window object (a Uniter FFI wrapper of the browser's Window).
     */
    public function getWindow(): mixed
    {
        return $this->window;
    }
}
