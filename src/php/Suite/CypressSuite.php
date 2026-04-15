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

use Tappet\Core\Automation\Field\FieldActionHandlerInterface;
use Tappet\Core\Automation\Interaction\InteractionHandlerInterface;
use Tappet\Core\Automation\Region\RegionAssertionHandlerInterface;
use Tappet\Core\Automation\State\StateAssertionHandlerInterface;
use Tappet\Cypress\Adapter\AdapterInterface;
use Tappet\Cypress\Adapter\DefaultAdapter;
use Tappet\Cypress\Automation\CypressAutomation;
use Tappet\Cypress\Automation\Field\TextFieldActionHandler;
use Tappet\Cypress\Automation\Interaction\ButtonInteractionHandler;
use Tappet\Cypress\Automation\Interaction\HyperlinkInteractionHandler;
use Tappet\Cypress\Automation\Region\TextRegionAssertionHandler;
use Tappet\Cypress\Automation\State\ExistsStateAssertionHandler;
use Tappet\Suite\Cli\CliSpec;
use Tappet\Suite\Cli\CliSpecInterface;
use Tappet\Suite\Result\ResultInterface;
use Tappet\Suite\Result\TestResult;
use Tappet\Suite\SuiteInterface;

/**
 * Class CypressSuite.
 *
 * Represents the test suite configuration for Tappet Cypress, allowing the suite implementation
 * to be configured via e.g. `tappet.cypress.config.php`.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class CypressSuite implements SuiteInterface
{
    /**
     * @var AdapterInterface
     */
    private $adapter;
    /**
     * @var string
     */
    private $cypressRoot;

    public function __construct(
        string $cypressRoot,
        AdapterInterface $adapter = new DefaultAdapter()
    ) {
        $this->adapter = $adapter;
        $this->cypressRoot = $cypressRoot;

        $adapter->getFieldActionRegistry()->registerFieldActionHandler('text', new TextFieldActionHandler());
        $adapter->getInteractionRegistry()->registerInteractionHandler('button', new ButtonInteractionHandler());
        $adapter->getInteractionRegistry()->registerInteractionHandler('hyperlink', new HyperlinkInteractionHandler());
        $adapter->getRegionAssertionRegistry()->registerRegionAssertionHandler('text', new TextRegionAssertionHandler());
        $adapter->getStateAssertionRegistry()->registerStateAssertionHandler('exists', new ExistsStateAssertionHandler());
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
    public function getAutomation(mixed $cy): CypressAutomation
    {
        return $this->adapter->getAutomation($cy);
    }

    /**
     * @inheritDoc
     */
    public function getCliSpec(): CliSpecInterface
    {
        return new CliSpec();
    }

    /**
     * @inheritDoc
     */
    public function registerFieldActionHandler(string $fieldType, FieldActionHandlerInterface $handler): void
    {
        $this->adapter->getFieldActionRegistry()->registerFieldActionHandler($fieldType, $handler);
    }

    /**
     * @inheritDoc
     */
    public function registerInteractionHandler(string $interactionType, InteractionHandlerInterface $handler): void
    {
        $this->adapter->getInteractionRegistry()->registerInteractionHandler($interactionType, $handler);
    }

    /**
     * @inheritDoc
     */
    public function registerRegionAssertionHandler(string $regionType, RegionAssertionHandlerInterface $handler): void
    {
        $this->adapter->getRegionAssertionRegistry()->registerRegionAssertionHandler($regionType, $handler);
    }

    /**
     * @inheritDoc
     */
    public function registerStateAssertionHandler(string $stateType, StateAssertionHandlerInterface $handler): void
    {
        $this->adapter->getStateAssertionRegistry()->registerStateAssertionHandler($stateType, $handler);
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
        ?string $filter,
        array $options
    ): ResultInterface {
        $envVars = [
            'tappetSuite' => $suiteName,
            'tappetApiBaseUrl' => $apiBaseUrl,
            'tappetApiKey' => $apiKey,
        ];

        if ($filter !== null) {
            $envVars['tappetFilter'] = $filter;
        }

        $envVarsString = implode(',', array_map(function ($key, $value) {
            return $key . '=' . $value;
        }, array_keys($envVars), $envVars));

        $command = $projectRoot . '/node_modules/.bin/cypress run' .
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
