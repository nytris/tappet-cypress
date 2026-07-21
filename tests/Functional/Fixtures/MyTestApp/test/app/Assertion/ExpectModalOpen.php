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

namespace Tappet\Cypress\Tests\Functional\Fixtures\MyTestApp\test\app\Assertion;

use Tappet\Cypress\Tests\Functional\Fixtures\MyTestApp\test\app\Transition\ModalOpenTransition;
use Tappet\Runner\Arrangement\ArrangementInterface;
use Tappet\Runner\Assertion\AssertionInterface;
use Tappet\Runner\Environment\EnvironmentInterface;

/**
 * Class ExpectModalOpen.
 *
 * Declares that the next logged transition must be a modal becoming visible.
 * May be used in either the arrangement or assertion stage of a scenario.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class ExpectModalOpen implements ArrangementInterface, AssertionInterface
{
    public function __construct(
        private readonly string $handle
    ) {
    }

    /**
     * @inheritDoc
     */
    public function perform(EnvironmentInterface $environment): void
    {
        $environment->assertTransition(new ModalOpenTransition($this->handle));
    }
}
