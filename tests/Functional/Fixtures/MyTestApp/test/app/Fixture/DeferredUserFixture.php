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

namespace Tappet\Cypress\Tests\Functional\Fixtures\MyTestApp\test\app\Fixture;

use Tappet\Common\Fixture\DeferredPurgeFixtureInterface;

/**
 * Class DeferredUserFixture.
 *
 * A user fixture whose purge must be deferred until the whole Cypress run's
 * Node.js controller process exits, rather than purged after the scenario
 * that loaded it concludes.
 */
class DeferredUserFixture extends UserFixture implements DeferredPurgeFixtureInterface
{
}
