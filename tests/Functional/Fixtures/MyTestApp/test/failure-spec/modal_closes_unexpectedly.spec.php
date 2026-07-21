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
use Tappet\Cypress\Tests\Functional\Fixtures\MyTestApp\test\app\Assertion\ExpectModalOpen;
use Tappet\Cypress\Tests\Functional\Fixtures\MyTestApp\test\app\Fixture\UserFixture;
use Tappet\Cypress\Tests\Functional\Fixtures\MyTestApp\test\app\Page\UserListPage;
use Tappet\Runner\Standard\Action\AssertionAction;
use Tappet\Runner\Standard\Action\Enact;
use Tappet\Runner\Standard\Arrangement\LoadMultipleFixtures;
use Tappet\Runner\Standard\Assertion\ExpectNewPage;
use Tappet\Runner\Tappet;

/**
 * This spec intentionally causes a test failure.
 *
 * The modal opens and ExpectModalOpen correctly consumes the ModalOpenTransition.
 * The modal then closes, logging a ModalClosedTransition. No ExpectModalClosed is
 * declared, so when the subsequent Enact triggers ->assertTransitionLogEmpty(),
 * it finds the unconsumed ModalClosedTransition entry and fails.
 */
Tappet::describe('Modal Failure -> Unexpected Close', [
    Tappet::it('should fail: modal closes without being declared')
        ->arrange(
            new LoadMultipleFixtures([
                'adam-admin' => new UserFixture('Adam', 'Admin', 'adam.admin@example.com'),
            ]),
            new LogInAs('adam-admin'),
            new ExpectNewPage(new UserListPage())
        )
        ->act(
            new Enact('open-add-user-modal'),
            new AssertionAction(new ExpectModalOpen('add-user')),
            new Enact('close-add-user-modal'),
            // No ExpectModalClosed declared - the ModalClosedTransition is unconsumed.
            // This next Enact triggers ->assertTransitionLogEmpty(), which finds it and fails.
            new Enact('open-add-user-modal')
        ),
]);
