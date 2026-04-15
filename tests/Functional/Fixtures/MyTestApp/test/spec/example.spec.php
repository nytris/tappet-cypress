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

use Tappet\Core\Standard\Action\Enact;
use Tappet\Core\Standard\Action\Type;
use Tappet\Core\Standard\Arrangement\LoadMultipleFixtures;
use Tappet\Core\Standard\Arrangement\OpenPage;
use Tappet\Core\Standard\Assertion\ExpectNewPage;
use Tappet\Core\Tappet;
use Tappet\Cypress\Tests\Functional\Fixtures\MyTestApp\test\app\Arrangement\LogInAs;
use Tappet\Cypress\Tests\Functional\Fixtures\MyTestApp\test\app\Assertion\ExpectFlash;
use Tappet\Cypress\Tests\Functional\Fixtures\MyTestApp\test\app\Fixture\UserFixture;
use Tappet\Cypress\Tests\Functional\Fixtures\MyTestApp\test\app\Page\UserEditPage;
use Tappet\Cypress\Tests\Functional\Fixtures\MyTestApp\test\app\Page\UserListPage;

Tappet::describe('User Management -> User', [
    Tappet::it('first name can be changed @mytag')
        ->arrange(
            new LoadMultipleFixtures([
                'adam-admin' => new UserFixture('Adam', 'Admin', 'adam.admin@example.com'),
                'john-user' => new UserFixture('John', 'Doe', 'john.doe@example.com'),
            ]),
            new LogInAs('adam-admin'),
            new OpenPage(new UserEditPage('john-user'))
        )
        ->act(
            new Type('first-name', 'Fred'),
            new Enact('save')
        )
        ->assert(
            new ExpectNewPage(new UserListPage()),
            new ExpectFlash('success', 'User saved successfully')
        ),

    Tappet::it('last name can be changed')
        ->arrange(
            new LoadMultipleFixtures([
                'adam-admin' => new UserFixture('Adam', 'Admin', 'adam.admin@example.com'),
                'john-user' => new UserFixture('John', 'Doe', 'john.doe@example.com'),
            ]),
            new LogInAs('adam-admin'),
            new OpenPage(new UserEditPage('john-user'))
        )
        ->act(
            new Type('last-name', 'Smith'),
            new Enact('save')
        )
        ->assert(
            new ExpectNewPage(new UserListPage()),
            new ExpectFlash('success', 'User saved successfully')
        ),
]);
