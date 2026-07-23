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
use Tappet\Cypress\Tests\Functional\Fixtures\MyTestApp\test\app\Assertion\ExpectFlash;
use Tappet\Cypress\Tests\Functional\Fixtures\MyTestApp\test\app\Assertion\ExpectModalClosed;
use Tappet\Cypress\Tests\Functional\Fixtures\MyTestApp\test\app\Assertion\ExpectModalOpen;
use Tappet\Cypress\Tests\Functional\Fixtures\MyTestApp\test\app\Fixture\UserFixture;
use Tappet\Cypress\Tests\Functional\Fixtures\MyTestApp\test\app\Matcher\Badge;
use Tappet\Cypress\Tests\Functional\Fixtures\MyTestApp\test\app\Page\UserEditPage;
use Tappet\Cypress\Tests\Functional\Fixtures\MyTestApp\test\app\Page\UserListPage;
use Tappet\Runner\Standard\Action\AssertionAction;
use Tappet\Runner\Standard\Action\ChooseRadioOption;
use Tappet\Runner\Standard\Action\Enact;
use Tappet\Runner\Standard\Action\Select;
use Tappet\Runner\Standard\Action\Type;
use Tappet\Runner\Standard\Arrangement\LoadMultipleFixtures;
use Tappet\Runner\Standard\Arrangement\OpenPage;
use Tappet\Runner\Standard\Assertion\ExpectList;
use Tappet\Runner\Standard\Assertion\ExpectNewPage;
use Tappet\Runner\Standard\Assertion\ExpectSelectedOption;
use Tappet\Runner\Standard\Assertion\ExpectSelectedRadioOption;
use Tappet\Runner\Standard\Assertion\ExpectTable;
use Tappet\Runner\Standard\Assertion\ExpectTextFieldValue;
use Tappet\Runner\Standard\Matcher\ExactText;
use Tappet\Runner\Standard\Matcher\Text;
use Tappet\Runner\Tappet;

Tappet::describe('User Management -> User', [
    Tappet::it('first name can be changed @mytag')
        ->arrange(
            new LoadMultipleFixtures([
                'adam-admin' => new UserFixture('Adam', 'Admin', 'adam.admin@example.com'),
                'john-user' => new UserFixture('John', 'Doe', 'john.doe@example.com'),
            ]),
            new LogInAs('adam-admin'),
            new ExpectNewPage(new UserListPage()),
            new OpenPage(new UserEditPage('john-user'))
        )
        ->act(
            new Type('first-name', 'Fred'),
            new AssertionAction(new ExpectTextFieldValue('first-name', 'Fred')),
            new Enact('save')
        )
        ->assert(
            new ExpectNewPage(new UserListPage()),
            new ExpectFlash('success', 'User saved successfully')
        ),

    Tappet::it('role and status can be changed')
        ->arrange(
            new LoadMultipleFixtures([
                'adam-admin' => new UserFixture('Adam', 'Admin', 'adam.admin@example.com'),
                'john-user' => new UserFixture('John', 'Doe', 'john.doe@example.com'),
            ]),
            new LogInAs('adam-admin'),
            new ExpectNewPage(new UserListPage()),
            new OpenPage(new UserEditPage('john-user'))
        )
        ->act(
            new Select('role', 'admin'),
            new AssertionAction(new ExpectSelectedOption('role', 'admin')),
            new ChooseRadioOption('status', 'inactive'),
            new AssertionAction(new ExpectSelectedRadioOption('status', 'inactive')),
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
            new ExpectNewPage(new UserListPage()),
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

Tappet::describe('User Management -> User List', [
    Tappet::it('user list and table show the expected users')
        ->arrange(
            new LoadMultipleFixtures([
                'adam-admin' => new UserFixture('Adam', 'Admin', 'adam.admin@example.com'),
                'john-user' => new UserFixture('John', 'Doe', 'john.doe@example.com'),
            ]),
            new LogInAs('adam-admin'),
            new ExpectNewPage(new UserListPage())
        )
        ->assert(
            new ExpectList('user-list', [
                new Text('Adam Admin'),
                new Text('John Doe'),
            ]),
            new ExpectTable('user-table', [
                [
                    'name' => new Text('Adam'),
                    'email' => new ExactText('adam.admin@example.com'),
                    'status' => new Badge('active'),
                ],
                [
                    'name' => new Text('John'),
                    'email' => new ExactText('john.doe@example.com'),
                    'status' => new Badge('active'),
                ],
            ])
        ),
]);

Tappet::describe('User Management -> Modal', [
    Tappet::it('add-user modal can be opened and closed')
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
            new Enact('close-add-user-modal')
        )
        ->assert(
            new ExpectModalClosed('add-user')
        ),
]);
