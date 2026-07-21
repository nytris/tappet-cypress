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

/**
 * PHPStan stubs for functions injected by the Uniter/JavaScript bridge at runtime.
 *
 * These are defined via `environment.defineCoercingFunction(...)` in `phpcore.config.ts`.
 */

function tappet_add_window_beforeload_handler(callable $handler): void
{
    throw new LogicException('Stub only.');
}

function tappet_add_window_load_handler(callable $handler): void
{
    throw new LogicException('Stub only.');
}

function tappet_get_base_url(): string
{
    throw new LogicException('Stub only.');
}

function tappet_get_cypress_api(): mixed
{
    throw new LogicException('Stub only.');
}

function tappet_get_cypress_project_root(): string
{
    throw new LogicException('Stub only.');
}

function tappet_get_describe(mixed $modelRepository): callable
{
    throw new LogicException('Stub only.');
}

function tappet_get_fixture_api(): object
{
    throw new LogicException('Stub only.');
}

function tappet_get_fixture_api_base_url(): string
{
    throw new LogicException('Stub only.');
}

function tappet_get_suite_name(): string
{
    throw new LogicException('Stub only.');
}

function tappet_init_transition_log(mixed $transitionLog): void
{
    throw new LogicException('Stub only.');
}

function tappet_set_base_url(string $baseUrl): void
{
    throw new LogicException('Stub only.');
}

function tappet_set_fixture_api_base_url(string $apiBaseUrl): void
{
    throw new LogicException('Stub only.');
}
