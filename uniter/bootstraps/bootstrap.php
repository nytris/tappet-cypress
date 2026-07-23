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
use Tappet\Cypress\Event\WindowBeforeLoadEvent;
use Tappet\Cypress\Event\WindowLoadEvent;
use Tappet\Cypress\Suite\CypressSuite;
use Tappet\Runner\Client\Client;
use Tappet\Runner\Configuration\ProxyConfiguration;
use Tappet\Runner\Environment\Environment;
use Tappet\Runner\Fixture\ModelRepository;
use Tappet\Runner\Project\ProjectRootResolver;
use Tappet\Runner\Tappet;
use Tappet\Runner\Transition\Log\TransitionLog;
use Tappet\Runner\Transition\NavigationTransition;
use Tappet\Suite\SuiteResolver;

$projectRootResolver = new ProjectRootResolver(new ReflectionClass(ClassLoader::class));
$suiteResolver = new SuiteResolver(CypressSuite::class, [
    tappet_get_cypress_project_root(),
    $projectRootResolver->resolveProjectRoot()
]);

$suite = $suiteResolver->resolveSuite(tappet_get_suite_name());

// TODO: Move to be instantiated by adapter / standardise?
$transitionLog = new TransitionLog();
tappet_init_transition_log($transitionLog);

$automation = $suite->getAutomation(tappet_get_cypress_api(), $transitionLog);

// Dispatch WindowBeforeLoadEvent to plugins whenever the AUT window is about to load.
tappet_add_window_beforeload_handler(function ($window) use ($suite): void {
    $suite->getEventDispatcher()->dispatch(new WindowBeforeLoadEvent($window));
});

// Dispatch WindowLoadEvent to plugins and push a NavigationTransition whenever the AUT window fully loads.
tappet_add_window_load_handler(function ($win) use ($automation, $suite): void {
    $suite->getEventDispatcher()->dispatch(new WindowLoadEvent($win));

    $loc = $win->location;
    $automation->pushTransition(new NavigationTransition($loc->href));
});

// API and AUT base URLs are both stored Node.js-side,
// so that they survive test runner re-hosts on AUT domain change.
$configuration = new ProxyConfiguration(
    getApiBaseUrlCallback: function (): string {
        return tappet_get_fixture_api_base_url();
    },
    getBaseUrlCallback: function (): string {
        return tappet_get_base_url();
    },
    setApiBaseUrlCallback: function (string $apiBaseUrl): void {
        tappet_set_fixture_api_base_url($apiBaseUrl);
    },
    setBaseUrlCallback: function (string $baseUrl): void {
        tappet_set_base_url($baseUrl);
    },
);

$client = new Client(
    eventDispatcher: $suite->getEventDispatcher(),
    configuration: $configuration,
    fixtureApi: tappet_get_fixture_api(),
);

$modelRepository = new ModelRepository($client);
$environment = new Environment($modelRepository, $automation, $configuration);

Tappet::initialise(tappet_get_describe($modelRepository), $environment);
