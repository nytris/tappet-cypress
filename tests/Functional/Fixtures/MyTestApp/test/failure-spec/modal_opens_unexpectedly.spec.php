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
use Tappet\Cypress\Tests\Functional\Fixtures\MyTestApp\test\app\Page\UserListPage;
use Tappet\Runner\Standard\Action\Enact;
use Tappet\Runner\Standard\Arrangement\LoadMultipleFixtures;
use Tappet\Runner\Standard\Assertion\ExpectNewPage;
use Tappet\Runner\Tappet;

/**
 * This spec intentionally causes a test failure.
 *
 * After the add-user modal opens (via button click), no ExpectModalOpen is declared.
 * The subsequent Enact triggers ->assertTransitionLogEmpty(), which finds the unconsumed
 * ModalOpenTransition entry and fails.
 */
Tappet::describe('Modal Failure -> Unexpected Open', [
    Tappet::it('should fail: modal opens without being declared')
        ->arrange(
            new LoadMultipleFixtures([
                'adam-admin' => new UserFixture('Adam', 'Admin', 'adam.admin@example.com'),
            ]),
            new LogInAs('adam-admin')
        )
        ->act(
            new Enact('open-add-user-modal'),
            // No ExpectModalOpen declared - the ModalOpenTransition is unconsumed.
            new Enact('close-add-user-modal')
        ),
]);
