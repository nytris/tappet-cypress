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

use Tappet\Core\Environment\Environment;
use Tappet\Core\Fixture\ModelRepository;
use Tappet\Core\Tappet;
use Tappet\Cypress\Automation\CypressAutomation;

$automation = new CypressAutomation(tappet_get_cypress_api());
$modelRepository = new ModelRepository(tappet_get_fixture_api());
$environment = new Environment($modelRepository, $automation);

Tappet::initialise(tappet_get_describe($modelRepository), $environment);
