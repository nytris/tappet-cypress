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

use Composer\Autoload\ClassLoader;
use Tappet\Core\Environment\Environment;
use Tappet\Core\Fixture\ModelRepository;
use Tappet\Core\Project\ProjectRootResolver;
use Tappet\Core\Tappet;
use Tappet\Cypress\Suite\CypressSuite;
use Tappet\Suite\SuiteResolver;

$projectRootResolver = new ProjectRootResolver(new ReflectionClass(ClassLoader::class));
$suiteResolver = new SuiteResolver(CypressSuite::class, [
    tappet_get_cypress_project_root(),
    $projectRootResolver->resolveProjectRoot()
]);

$suite = $suiteResolver->resolveSuite(tappet_get_suite_name());

$automation = $suite->getAutomation(tappet_get_cypress_api());
$modelRepository = new ModelRepository(tappet_get_fixture_api());
$environment = new Environment($modelRepository, $automation, tappet_get_base_url());

Tappet::initialise(tappet_get_describe($modelRepository), $environment);
