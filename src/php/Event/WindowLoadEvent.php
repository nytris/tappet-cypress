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
 * Class WindowLoadEvent.
 *
 * Fired when each AUT window fully loads, allowing plugins to react
 * to page navigations and perform post-load setup.
 *
 * The $window property is a Uniter FFI wrapper of the browser's Window object,
 * so JS methods/properties (e.g. $window->location) can be accessed from PHP.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class WindowLoadEvent implements EventInterface
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
