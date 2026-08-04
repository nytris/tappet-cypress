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
use Tappet\Runner\Standard\Arrangement\LoadMultipleFixtures;
use Tappet\Runner\Standard\Assertion\ExpectNewPage;
use Tappet\Runner\Tappet;

/**
 * This spec intentionally causes a test failure.
 *
 * ExpectModalOpen is declared, but the button to open the modal is never clicked,
 * so no ModalOpenTransition is ever logged. The ->waitForTransition() retry times out.
 */
Tappet::describe('Modal Failure -> Open Not Detected', [
    Tappet::it('should fail: expected ModalOpenTransition is never detected')
        ->arrange(
            new LoadMultipleFixtures([
                'adam-admin' => new UserFixture('Adam', 'Admin', 'adam.admin@example.com'),
            ]),
            new LogInAs('adam-admin')
        )
        ->assert(
            // No Enact to open the modal - the ModalOpenTransition never fires.
            new ExpectModalOpen('add-user')
        ),
]);
