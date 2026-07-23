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

/**
 * Class Context.
 *
 * Wraps the Cypress chainable target for a cell/item to be matched.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class Context implements ContextInterface
{
    public function __construct(
        private readonly mixed $target
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getTarget(): mixed
    {
        return $this->target;
    }
}
