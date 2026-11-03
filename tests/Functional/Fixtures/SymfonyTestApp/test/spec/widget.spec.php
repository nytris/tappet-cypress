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

use Tappet\Cypress\Tests\Functional\Fixtures\SymfonyTestApp\test\app\Assertion\ExpectWidgetSecretCode;
use Tappet\Cypress\Tests\Functional\Fixtures\SymfonyTestApp\test\app\Fixture\WidgetFixture;
use Tappet\Cypress\Tests\Functional\Fixtures\SymfonyTestApp\test\app\Page\WidgetPage;
use Tappet\Runner\Standard\Arrangement\LoadFixture;
use Tappet\Runner\Standard\Arrangement\OpenPage;
use Tappet\Runner\Standard\Assertion\ExpectRegionContains;
use Tappet\Runner\Standard\Matcher\ExactText;
use Tappet\Runner\Tappet;

Tappet::describe('Widget', [
    Tappet::it('widget is loaded and displayed via the real Tappet Symfony bundle, and its hidden secret code can be asserted via a remote state assertion')
        ->arrange(
            new LoadFixture('my-widget', new WidgetFixture('Test Widget', 'shh-secret-123')),
            new OpenPage(new WidgetPage('my-widget'))
        )
        ->assert(
            new ExpectRegionContains('widget-name', 'Test Widget'),
            new ExpectWidgetSecretCode(new ExactText('shh-secret-123'))
        ),
]);
