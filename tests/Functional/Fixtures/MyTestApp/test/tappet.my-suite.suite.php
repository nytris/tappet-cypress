<?php

declare(strict_types=1);

use Tappet\Cypress\Suite\CypressSuite;
use Tappet\Cypress\Tests\Functional\Fixtures\MyTestApp\test\app\Plugin\TestPlugin;

// Only set by CypressFunctionalTest when exercising the Webpack cache feature end-to-end.
$webpackCacheDirectory = getenv('TAPPET_TEST_WEBPACK_CACHE_DIR') ?: null;

$suite = new CypressSuite(__DIR__, webpackCacheDirectory: $webpackCacheDirectory);

$suite->addPlugin(new TestPlugin());

return $suite;
