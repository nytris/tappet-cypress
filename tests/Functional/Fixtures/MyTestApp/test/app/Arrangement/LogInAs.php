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

use Tappet\Cypress\Tests\Functional\Fixtures\MyTestApp\test\app\Fixture\UserModel;
use Tappet\Cypress\Tests\Functional\Fixtures\MyTestApp\test\app\Page\UserListPage;
use Tappet\Runner\Arrangement\ArrangementInterface;
use Tappet\Runner\Environment\EnvironmentInterface;
use Tappet\Runner\Transition\PageTransition;

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

        $environment->visitUrl('/_tappet/auth/login/' . $userId);

        $environment->assertTransition(new PageTransition(new UserListPage(), $environment));
    }
}
