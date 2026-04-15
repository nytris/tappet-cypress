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

class UserListPage implements PageInterface
{
    public function buildUrl(EnvironmentInterface $environment): string
    {
        return '/users';
    }
}
