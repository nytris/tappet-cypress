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

namespace Tappet\Cypress\Tests\Functional\Fixtures\MyTestApp\test\app\Matcher;

use Tappet\Runner\Matcher\MatcherInterface;

/**
 * Custom fixture matcher, matching a Badge UI widget wrapper via its `data-ui-badge="<handle>"` attribute.
 */
class Badge implements MatcherInterface
{
    public function __construct(
        private readonly string $handle
    ) {
    }

    public function getHandle(): string
    {
        return $this->handle;
    }
}
