# Tappet - Enjoyable GUI testing with Tappet, using Cypress

[![Build Status](https://github.com/nytris/tappet-cypress/workflows/CI/badge.svg)](https://github.com/nytris/tappet-cypress/actions?query=workflow%3ACI)

[EXPERIMENTAL] Cypress adapter for Tappet GUI testing.

See the [Tappet core README][] for the core Tappet concepts - arrange/act/assert specs,
fixtures, pages, and in particular [transitions][Tappet transitions],
the mechanism used to detect and assert on whole-page-state changes such as navigation or a modal opening/closing.
This README only covers what's specific to the Cypress adapter, including how custom transition detection is wired up here.

## Installation

### Prerequisites

- PHP 8.1 or later
- [Composer](https://getcomposer.org/)

### 1. Install the adapter

```bash
composer require --dev tappet/cypress
```

```bash
npm install --save-dev cypress @tappet/cypress
```

### 2. Configure the adapter

Create `tappet.{suite-name}.suite.php` in the root of your project, for example:

`tappet.cypress.suite.php` (to name the suite `cypress`):
```php
<?php

declare(strict_types=1);

use Tappet\Cypress\Suite\CypressSuite;

$suite = new CypressSuite(
    cypressRoot: __DIR__ . '/tests/gui' // Points to the nested Cypress project created for Tappet.
);

$suite->addPlugin(new MyPlugin());

return $suite;

```

### 3. Install Cypress

```bash
node_modules/.bin/cypress install

mkdir -p tests/gui
```

Inside the nested Cypress project directory referenced by `cypressRoot` above (e.g. `tests/gui`), scaffold a Cypress
project along with this package's JS/TS half, which provides the Uniter `defineConfig` factory and Cypress plugin
that bridge PHP and JavaScript:

```bash
cd tests/gui

# Cypress does not support an "init" CLI command: instead, run `open` once to scaffold a Cypress project.
# Continue until you get to the "We added the following files to your project:" stage, then you can close Cypress.
../../node_modules/.bin/cypress open

# By this point, the file `cypress.config.js` should exist:
ls -l cypress.config.js
```

Update `cypress.config.js` to register tappet/cypress:

`cypress.config.js`
```js
const { defineConfig } = require('cypress');
const { register: tappetPlugin } = require('@tappet/cypress/cypress/plugin');

module.exports = defineConfig({
    e2e: {
        setupNodeEvents(on, config) {
            // Configure Cypress to run Tappet specs.
            return tappetPlugin(on, config);
        },
        // Path to where your Tappet PHP spec files will live.
        specPattern: 'spec/**/*.spec.php',
    },
    // Other config, e.g.:
    defaultCommandTimeout: 20000,
    pageLoadTimeout: 60000,
});
```

In the same directory, create a `uniter.config.js` to configure [Uniter][],
which is used to run PHP code under a JavaScript environment:

`uniter.config.js`
```js
const { defineConfig } = require('@tappet/cypress/uniter/defineConfig');

/*
 * Configures Uniter, which is a PHP-to-JavaScript transpiler.
 *
 * This configuration allows Cypress to run Tappet specs written in PHP.
 */
module.exports = defineConfig(__dirname, '../../', {
    include: [
        // If your suite is named "cypress".
        'tappet.cypress.suite.php',
        // It is recommended to .gitignore this `.local.php` file: it allows you to keep local overrides
        // for the config that you do not want to commit, e.g. local dev environment URLs.
        'tappet.cypress.suite.local.php',
        'tests/gui/app/**/*.php',

        // Include any other needed PHP files here, including those from `vendor/`.
    ],
});
```

Now you can create your specs, for example `tests/gui/spec/Workflow/create_workflow.spec.php`.
Note that the `spec/` part of the path was defined by `specPattern: 'spec/**/*.spec.php'` in `cypress.config.js` above.

## Custom transition detection

Tappet core defines what a transition *is* (`TransitionInterface`) and how specs assert on one
(`EnvironmentInterface::assertTransition()`). However, detection of a transition occurring is necessarily
adapter-specific, since it depends on hooking into the underlying test tool. This package detects transitions
by dispatching events during the Cypress test run that plugins can listen to, and pushing detected transitions
onto the shared transition log via `CypressAutomationInterface::pushTransition(TransitionInterface $transition): void`.

The built-in `NavigationTransition` is pushed automatically by this adapter whenever the AUT's window fires a load
event; for plain page navigation, there is nothing you need to wire up. Anything else, such as a modal appearing or
disappearing, needs a plugin.

### Writing a plugin

A plugin implements `Tappet\Suite\Plugin\PluginInterface` (from `tappet/tappet` core) and declares a map of
event class to listener. The most relevant event for transition detection is `WindowBeforeLoadEvent`, fired
before each AUT window load with access to `$window`, a [Uniter][] FFI wrapper of the browser's `Window` object so
that JS methods/properties (e.g. `$window->document`, `$window->MutationObserver`) can be called/used from PHP.

Continuing the modal example from the core README, here's how `tappet/tappet`'s own test suite detects
`data-ui-modal` elements toggling their `hidden` attribute, using a `MutationObserver`, and pushes the
corresponding `ModalOpenTransition`/`ModalClosedTransition`:

```php
class TestPlugin implements PluginInterface
{
    public function getListeners(): array
    {
        return [
            WindowBeforeLoadEvent::class => function (EventInterface $event, CypressAutomationInterface $automation): void {
                $window = $event->getWindow();

                $observer = new $window->MutationObserver(
                    function ($mutations) use ($automation): void {
                        foreach ($mutations as $mutation) {
                            if ($mutation->type !== 'attributes' || $mutation->attributeName !== 'hidden') {
                                return;
                            }

                            $el = $mutation->target;
                            $handle = $el->getAttribute('data-ui-modal');

                            if (!$handle) {
                                return;
                            }

                            $isVisible = !$el->hasAttribute('hidden');

                            $automation->pushTransition(
                                $isVisible
                                    ? new ModalOpenTransition($handle)
                                    : new ModalClosedTransition($handle)
                            );
                        }
                    }
                );

                $window->document->addEventListener(
                    'DOMContentLoaded',
                    function () use ($observer, $window): void {
                        $observer->observe($window->document->body, [
                            'subtree' => true,
                            'attributes' => true,
                            'attributeFilter' => ['hidden'],
                        ]);
                    }
                );
            },
        ];
    }
}
```

`ModalOpenTransition`/`ModalClosedTransition` and their matching `ExpectModalOpen`/`ExpectModalClosed`
arrangement/assertion classes are just application-level `TransitionInterface`/`ArrangementInterface`/
`AssertionInterface` implementations - see the [core README's transitions section][Tappet transitions]
for how those interfaces work. Nothing about them is Cypress-specific; only the detection plugin above is.

### Registering a plugin

Register the plugin against the `CypressSuite` instance returned by `tappet.{suite-name}.suite.php`:

```php
<?php

declare(strict_types=1);

use Tappet\Cypress\Suite\CypressSuite;

$suite = new CypressSuite(__DIR__);

$suite->addPlugin(new TestPlugin());

return $suite;
```

#### Custom behaviour extension

Other `*InitEvent` classes in `Tappet\Cypress\Event` (`FieldActionInitEvent`, `FieldAssertionInitEvent`,
`InteractionInitEvent`, `MatchHandlerInitEvent`, `RegionAssertionInitEvent`, `StateAssertionInitEvent`) follow
the same plugin-listener mechanism, but are for registering custom field actions, field assertions, interactions,
matchers, matchers, region assertions and state assertions respectively rather than for transition detection.

For example:
```php
<?php

declare(strict_types=1);

namespace MyApp\Tests\Tappet\Plugin;

use MyApp\Tests\Tappet\MatchHandler\BadgeMatchHandler;
use Tappet\Common\Event\EventInterface;
use Tappet\Cypress\Automation\CypressAutomationInterface;
use Tappet\Cypress\Event\MatchHandlerInitEvent;
use Tappet\Suite\Plugin\PluginInterface;

/**
 * @implements PluginInterface<CypressAutomationInterface>
 */
class MyPlugin implements PluginInterface
{
    public function getListeners(): array
    {
        return [
            MatchHandlerInitEvent::class => function (EventInterface $event): void {
                /** @var MatchHandlerInitEvent $event */
                $event->registerMatchHandler('badge', new BadgeMatchHandler($event->getAutomation()));
            },
        ];
    }
}

```

[Tappet core README]: https://github.com/nytris/tappet
[Tappet transitions]: https://github.com/nytris/tappet#transitions
[Uniter]: https://github.com/asmblah/uniter
