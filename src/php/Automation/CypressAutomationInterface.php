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

namespace Tappet\Cypress\Automation;

use Tappet\Cypress\Automation\Matcher\ContextInterface;
use Tappet\Runner\Automation\AutomationInterface;

/**
 * Interface CypressAutomationInterface.
 *
 * Represents the automation layer of a test scenario, where we integrate with Cypress.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
interface CypressAutomationInterface extends AutomationInterface
{
    /**
     * Fetches the prefix used for UI automation `data-` attributes.
     */
    public function getAttributePrefix(): string;

    /**
     * Fetches the underlying Cypress `cy` object.
     */
    public function getCy(): mixed;

    /**
     * Resolves the underlying Cypress chainable target wrapped by the given context.
     */
    public function resolve(ContextInterface $context): mixed;
}
