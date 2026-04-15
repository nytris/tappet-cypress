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

namespace Tappet\Cypress\Tests\Functional\Fixtures\MyTestApp\test\app\Arrangement;

use Tappet\Core\Arrangement\ArrangementInterface;
use Tappet\Core\Environment\EnvironmentInterface;
use Tappet\Cypress\Tests\Functional\Fixtures\MyTestApp\test\app\Fixture\UserModel;

class LogInAs implements ArrangementInterface
{
    /**
     * @var string
     */
    private $handle;

    public function __construct(string $handle)
    {
        $this->handle = $handle;
    }

    public function perform(EnvironmentInterface $environment): void
    {
        $userId = $environment->getFixtureModel(UserModel::class, $this->handle)->getId();

        $environment->visitPage('/_tappet/auth/login/' . $userId);
    }
}
