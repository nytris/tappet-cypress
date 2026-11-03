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

// Symfony 5.4's ErrorHandler component references the (deprecated-to-reference-but-still-defined)
// E_STRICT constant under PHP 8.4+, and Xdebug (when enabled locally) renders that deprecation
// notice as inline HTML - corrupting the JSON responses the Tappet API endpoints below return.
error_reporting(E_ALL & ~E_DEPRECATED);

require dirname(__DIR__, 5) . '/vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Tappet\Cypress\Tests\Functional\Fixtures\SymfonyTestApp\src\Kernel;

// Force a fixed session ID, exactly as MyTestApp's `web/index.php` does, so that the
// cookie-less `cy.task(...)` HTTP calls used to load/purge fixtures and fetch remote state
// share the same session as the actual browser page requests within a run.
session_id('tappet-test-symfony');
session_start();

$kernel = new Kernel('test', false);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
