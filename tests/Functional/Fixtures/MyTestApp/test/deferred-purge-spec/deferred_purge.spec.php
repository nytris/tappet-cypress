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

use Tappet\Cypress\Tests\Functional\Fixtures\MyTestApp\test\app\Fixture\DeferredUserFixture;
use Tappet\Cypress\Tests\Functional\Fixtures\MyTestApp\test\app\Fixture\UserFixture;
use Tappet\Cypress\Tests\Functional\Fixtures\MyTestApp\test\app\Page\UserListPage;
use Tappet\Runner\Standard\Arrangement\LoadMultipleFixtures;
use Tappet\Runner\Standard\Arrangement\OpenPage;
use Tappet\Runner\Standard\Assertion\ExpectList;
use Tappet\Runner\Standard\Matcher\Text;
use Tappet\Runner\Tappet;

/*
 * This spec lives in deferred-purge-spec/ (not spec/) so that it only runs when explicitly
 * targeted via specPattern, keeping it isolated from the scenario-count assertions the other
 * functional tests make against the default spec/ directory.
 *
 * A deferred-purge fixture's model must survive the purge that happens between scenarios
 * (via ModelRepository::purge() in Mocha's beforeEach) - it is only purged once the whole
 * Cypress run's Node.js controller process exits. That final purge is verified separately, by
 * CypressFunctionalTest asserting on the fixture app's state after the "cypress run" process
 * this spec runs in has fully exited.
 */
Tappet::describe('Deferred Purge', [
    Tappet::it('loads a deferred-purge user alongside a normal user')
        ->arrange(
            new LoadMultipleFixtures([
                'persistent' => new DeferredUserFixture('Persistent', 'User', 'persistent.user@example.com'),
                'temporary' => new UserFixture('Temporary', 'User', 'temporary.user@example.com'),
            ]),
            new OpenPage(new UserListPage())
        )
        ->assert(
            new ExpectList('user-list', [
                new Text('Persistent User'),
                new Text('Temporary User'),
            ])
        ),

    // Nothing is (re)loaded here - the previous scenario's models were purged in between by
    // Mocha's beforeEach, but the deferred-purge one must still be present.
    Tappet::it('still shows the deferred-purge user without reloading it, once the normal user has been purged')
        ->arrange(
            new OpenPage(new UserListPage())
        )
        ->assert(
            new ExpectList('user-list', [
                new Text('Persistent User'),
            ])
        ),
]);
