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

namespace Tappet\Cypress\Suite;

use Tappet\Common\Event\EventDispatcherInterface;
use Tappet\Common\Event\EventInterface;
use Tappet\Common\Event\EventListenerRegistryInterface;
use Tappet\Cypress\Adapter\AdapterInterface;
use Tappet\Cypress\Adapter\DefaultAdapter;
use Tappet\Cypress\Automation\CypressAutomationInterface;
use Tappet\Cypress\Automation\Field\CheckboxFieldActionHandler;
use Tappet\Cypress\Automation\Field\FileFieldActionHandler;
use Tappet\Cypress\Automation\Field\RadioFieldActionHandler;
use Tappet\Cypress\Automation\Field\RadioFieldAssertionHandler;
use Tappet\Cypress\Automation\Field\SelectFieldActionHandler;
use Tappet\Cypress\Automation\Field\SelectFieldAssertionHandler;
use Tappet\Cypress\Automation\Field\TextFieldActionHandler;
use Tappet\Cypress\Automation\Field\TextFieldAssertionHandler;
use Tappet\Cypress\Automation\Interaction\ButtonInteractionHandler;
use Tappet\Cypress\Automation\Interaction\HyperlinkInteractionHandler;
use Tappet\Cypress\Automation\Matcher\TextMatchHandler;
use Tappet\Cypress\Automation\Region\ListRegionAssertionHandler;
use Tappet\Cypress\Automation\Region\TableRegionAssertionHandler;
use Tappet\Cypress\Automation\Region\TextRegionAssertionHandler;
use Tappet\Cypress\Automation\State\ExistsStateAssertionHandler;
use Tappet\Cypress\Event\FieldActionInitEvent;
use Tappet\Cypress\Event\FieldAssertionInitEvent;
use Tappet\Cypress\Event\InteractionInitEvent;
use Tappet\Cypress\Event\MatchHandlerInitEvent;
use Tappet\Cypress\Event\RegionAssertionInitEvent;
use Tappet\Cypress\Event\StateAssertionInitEvent;
use Tappet\Runner\Transition\Log\TransitionLogInterface;
use Tappet\Suite\Cli\CliOption;
use Tappet\Suite\Cli\CliSpec;
use Tappet\Suite\Cli\CliSpecInterface;
use Tappet\Suite\Plugin\PluginInterface;
use Tappet\Suite\Result\ResultInterface;
use Tappet\Suite\Result\TestResult;
use Tappet\Suite\SuiteInterface;

/**
 * Class CypressSuite.
 *
 * Represents the test suite configuration for Tappet Cypress, allowing the suite implementation
 * to be configured via e.g. `tappet.cypress.config.php`.
 *
 * @implements SuiteInterface<CypressAutomationInterface>
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class CypressSuite implements EventListenerRegistryInterface, SuiteInterface
{
    /**
     * @var array<PluginInterface<CypressAutomationInterface>>
     */
    private array $plugins = [];

    public function __construct(
        private readonly string $cypressRoot,
        private readonly AdapterInterface $adapter = new DefaultAdapter(),
        private readonly ?string $webpackCacheDirectory = null
    ) {
        $eventDispatcher = $adapter->getEventDispatcher();

        // Register built-in handlers.
        $eventDispatcher->addEventListener(FieldActionInitEvent::class, function (FieldActionInitEvent $event): void {
            $automation = $event->getAutomation();

            $event->registerFieldActionHandler('checkbox', new CheckboxFieldActionHandler($automation));
            $event->registerFieldActionHandler('file', new FileFieldActionHandler($automation));
            $event->registerFieldActionHandler('radio', new RadioFieldActionHandler($automation));
            $event->registerFieldActionHandler('select', new SelectFieldActionHandler($automation));
            $event->registerFieldActionHandler('text', new TextFieldActionHandler($automation));
        });

        $eventDispatcher->addEventListener(FieldAssertionInitEvent::class, function (FieldAssertionInitEvent $event): void {
            $automation = $event->getAutomation();

            $event->registerFieldAssertionHandler('radio', new RadioFieldAssertionHandler($automation));
            $event->registerFieldAssertionHandler('select', new SelectFieldAssertionHandler($automation));
            $event->registerFieldAssertionHandler('text', new TextFieldAssertionHandler($automation));
        });

        $eventDispatcher->addEventListener(InteractionInitEvent::class, function (InteractionInitEvent $event): void {
            $automation = $event->getAutomation();

            $event->registerInteractionHandler('button', new ButtonInteractionHandler($automation));
            $event->registerInteractionHandler('hyperlink', new HyperlinkInteractionHandler($automation));
        });

        $eventDispatcher->addEventListener(MatchHandlerInitEvent::class, function (MatchHandlerInitEvent $event): void {
            $event->registerMatchHandler('default', new TextMatchHandler($event->getAutomation()));
        });

        $eventDispatcher->addEventListener(RegionAssertionInitEvent::class, function (RegionAssertionInitEvent $event): void {
            $automation = $event->getAutomation();
            $matcherRegistry = $event->getMatcherRegistry();

            $event->registerRegionAssertionHandler('list', new ListRegionAssertionHandler($automation, $matcherRegistry));
            $event->registerRegionAssertionHandler('table', new TableRegionAssertionHandler($automation, $matcherRegistry));
            $event->registerRegionAssertionHandler('text', new TextRegionAssertionHandler($automation));
        });

        $eventDispatcher->addEventListener(StateAssertionInitEvent::class, function (StateAssertionInitEvent $event): void {
            $event->registerStateAssertionHandler('exists', new ExistsStateAssertionHandler($event->getAutomation()));
        });
    }

    /**
     * @inheritDoc
     */
    public function addEventListener(string $eventClass, callable $listener): void
    {
        $this->adapter->getEventDispatcher()->addEventListener($eventClass, $listener);
    }

    /**
     * @inheritDoc
     */
    public function addPlugin(PluginInterface $plugin): void
    {
        $this->plugins[] = $plugin;
    }

    /**
     * Fetches the implementation of the Tappet Cypress adapter.
     */
    public function getAdapter(): AdapterInterface
    {
        return $this->adapter;
    }

    /**
     * Fetches the implementation of the Cypress automation.
     */
    public function getAutomation(mixed $cy, TransitionLogInterface $transitionLog): CypressAutomationInterface
    {
        $automation = $this->adapter->getAutomation($cy, $transitionLog);
        $eventDispatcher = $this->adapter->getEventDispatcher();

        foreach ($this->plugins as $plugin) {
            foreach ($plugin->getListeners() as $eventClass => $listener) {
                $eventDispatcher->addEventListener(
                    $eventClass,
                    function (EventInterface $event) use ($listener, $automation): void {
                        $listener($event, $automation);
                    }
                );
            }
        }

        $eventDispatcher->dispatch(new FieldActionInitEvent($this->adapter, $automation));
        $eventDispatcher->dispatch(new FieldAssertionInitEvent($this->adapter, $automation));
        $eventDispatcher->dispatch(new InteractionInitEvent($this->adapter, $automation));
        $eventDispatcher->dispatch(new MatchHandlerInitEvent($this->adapter, $automation));
        $eventDispatcher->dispatch(new RegionAssertionInitEvent($this->adapter, $automation));
        $eventDispatcher->dispatch(new StateAssertionInitEvent($this->adapter, $automation));

        return $automation;
    }

    /**
     * @inheritDoc
     */
    public function getCliSpec(): CliSpecInterface
    {
        return new CliSpec([
            // e.g. "open" or "run".
            new CliOption('mode', 'Cypress mode', false, false),
        ]);
    }

    /**
     * Fetches the event dispatcher.
     */
    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->adapter->getEventDispatcher();
    }

    /**
     * @inheritDoc
     */
    public function removeEventListener(string $eventClass, callable $listener): void
    {
        $this->adapter->getEventDispatcher()->removeEventListener($eventClass, $listener);
    }

    /**
     * @inheritDoc
     */
    public function run(
        string $projectRoot,
        string $suiteName,
        string $baseUrl,
        string $apiBaseUrl,
        string $apiKey,
        bool $apiTlsVerification,
        ?string $filter,
        array $options
    ): ResultInterface {
        $envVars = [
            'tappetSuite' => $suiteName,
            'tappetApiBaseUrl' => $apiBaseUrl,
            'tappetApiKey' => $apiKey,
            'tappetApiTlsVerification' => $apiTlsVerification ? 'true' : 'false',
        ];

        if ($filter !== null) {
            $envVars['tappetFilter'] = $filter;
        }

        if ($this->webpackCacheDirectory !== null) {
            $envVars['tappetWebpackCacheDirectory'] = $this->webpackCacheDirectory;
        }

        $envVarsString = implode(',', array_map(function ($key, $value) {
            return $key . '=' . $value;
        }, array_keys($envVars), $envVars));

        $command = $projectRoot . '/node_modules/.bin/cypress ' .
            ($options['mode'] ?? 'run') .
            ' --config baseUrl=' . escapeshellarg($baseUrl) .
            ' -e ' . escapeshellarg($envVarsString);

        // Use Cypress root as the working directory so that `cypress.config.js` can be discovered automatically.
        $cwd = $this->cypressRoot;

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptorSpec, $pipes, $cwd);

        fclose($pipes[0]);

        $stdoutOutput = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $stderrOutput = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        $output = $stdoutOutput . ($stderrOutput !== '' ? "\nSTDERR:\n" . $stderrOutput : '');

        return new TestResult($output, $exitCode !== 0);
    }
}
