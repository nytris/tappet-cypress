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

use Tappet\Cypress\Tests\Functional\Fixtures\MyTestApp\test\app\Fixture\UserFixture;
use Tappet\Cypress\Tests\Functional\Fixtures\MyTestApp\test\app\Page\UserListPage;
use Tappet\Runner\Standard\Arrangement\LoadMultipleFixtures;
use Tappet\Runner\Standard\Arrangement\OpenPage;
use Tappet\Runner\Standard\Assertion\ExpectList;
use Tappet\Runner\Standard\Matcher\Text;
use Tappet\Runner\Tappet;

/**
 * This spec intentionally causes a test failure.
 *
 * The scenario itself (loading a fixture, opening the list page and asserting its
 * contents) succeeds. The fixture's email address is a sentinel the fixture
 * app's web/index.php `DELETE /.well-known/tappet/fixtures` route recognises, responding
 * with a 500 to simulate the Tappet API failing to purge the fixture once the scenario
 * finishes. That purge failure must cause this Cypress test to fail overall, even
 * though every arrange/act/assert step before it succeeded.
 */
Tappet::describe('Purge Failure', [
    Tappet::it('should fail: fixture purge request fails after a scenario otherwise passes')
        ->arrange(
            new LoadMultipleFixtures([
                'doomed' => new UserFixture('Doomed', 'User', 'cause-purge-failure@example.com'),
            ]),
            new OpenPage(new UserListPage())
        )
        ->assert(
            new ExpectList('user-list', [
                new Text('Doomed User'),
            ])
        ),
]);
