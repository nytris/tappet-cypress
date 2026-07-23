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

namespace Tappet\Cypress\Automation\Matcher;

use Tappet\Runner\Matcher\ContextInterface as CoreContextInterface;

/**
 * Interface ContextInterface.
 *
 * Cypress-specific extension of the (opaque) core ContextInterface, exposing the underlying
 * Cypress chainable target so that CypressAutomation::resolve(...) can unwrap it in a typed way.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
interface ContextInterface extends CoreContextInterface
{
    /**
     * Fetches the underlying Cypress chainable target wrapped by this context.
     */
    public function getTarget(): mixed;
}
