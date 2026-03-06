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

use Tappet\Core\Automation\AutomationInterface;

class CypressAutomation implements AutomationInterface
{
    /**
     * @var mixed
     */
    private $cy;

    public function __construct(mixed $cy)
    {
        $this->cy = $cy;
    }

    public function assertPage(string $url): void
    {
        $this->cy->url()->should('eq', $url);
    }

    public function typeField(string $fieldHandle, string $text): void
    {
        $this->cy->get('[data-tappet-field="' . $fieldHandle . '"]')->type($text);
    }

    public function visitPage(string $url): void
    {
        $this->cy->visit($url);
    }
}
