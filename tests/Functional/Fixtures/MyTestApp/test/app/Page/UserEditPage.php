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

namespace Tappet\Cypress\Tests\Functional\Fixtures\MyTestApp\test\app\Page;

use Tappet\Core\Environment\EnvironmentInterface;
use Tappet\Core\Page\PageInterface;
use Tappet\Cypress\Tests\Functional\Fixtures\MyTestApp\test\app\Fixture\UserModel;

class UserEditPage implements PageInterface
{
    /**
     * @var string
     */
    private $userFixtureHandle;

    public function __construct(string $userFixtureHandle)
    {
        $this->userFixtureHandle = $userFixtureHandle;
    }

    public function buildUrl(EnvironmentInterface $environment): string
    {
        return '/users/' . $environment->getFixtureModel(UserModel::class, $this->userFixtureHandle)->getId();
    }
}
