<?php

declare(strict_types=1);

use Tappet\Cypress\Suite\CypressSuite;
use Tappet\Cypress\Tests\Functional\Fixtures\MyTestApp\test\app\Plugin\TestPlugin;

$suite = new CypressSuite(__DIR__);

$suite->addPlugin(new TestPlugin());

return $suite;
