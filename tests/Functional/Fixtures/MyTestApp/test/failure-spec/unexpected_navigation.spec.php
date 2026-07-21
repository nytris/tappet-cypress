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

use Tappet\Cypress\Tests\Functional\Fixtures\MyTestApp\test\app\Arrangement\LogInAs;
use Tappet\Cypress\Tests\Functional\Fixtures\MyTestApp\test\app\Fixture\UserFixture;
use Tappet\Cypress\Tests\Functional\Fixtures\MyTestApp\test\app\Page\UserEditPage;
use Tappet\Runner\Standard\Action\Enact;
use Tappet\Runner\Standard\Action\Type;
use Tappet\Runner\Standard\Arrangement\LoadMultipleFixtures;
use Tappet\Runner\Standard\Arrangement\OpenPage;
use Tappet\Runner\Tappet;

/**
 * This spec intentionally causes a test failure.
 *
 * After opening the user edit page, it clicks the Cancel link (which navigates away
 * to the user list), then attempts to type in a field. Because no ExpectNewPage or
 * Visit declared the navigation, Tappet asserts the URL still matches the edit page -
 * which it no longer does - and Cypress fails the test.
 */
Tappet::describe('Unexpected Navigation', [
    Tappet::it('should fail: types into a field after navigating away without ExpectNewPage')
        ->arrange(
            new LoadMultipleFixtures([
                'adam-admin' => new UserFixture('Adam', 'Admin', 'adam.admin@example.com'),
                'john-user' => new UserFixture('John', 'Doe', 'john.doe@example.com'),
            ]),
            new LogInAs('adam-admin'),
            new OpenPage(new UserEditPage('john-user'))
        )
        ->act(
            new Enact('cancel'),
            new Type('first-name', 'Fred')
        ),
]);
