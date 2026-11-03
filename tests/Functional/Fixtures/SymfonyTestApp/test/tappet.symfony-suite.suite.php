<?php

declare(strict_types=1);

use Tappet\Cypress\Suite\CypressSuite;
use Tappet\Cypress\Tests\Functional\Fixtures\SymfonyTestApp\test\app\Plugin\WidgetTestPlugin;

$suite = new CypressSuite(__DIR__);

$suite->addPlugin(new WidgetTestPlugin());

return $suite;
