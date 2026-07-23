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

namespace Tappet\Cypress\Tests\Functional;

/**
 * Class CypressFunctionalTest.
 *
 * Starts a PHP built-in web server serving the MyTestApp fixture, runs Cypress
 * against it, and asserts that all Cypress tests pass.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class CypressFunctionalTest extends AbstractFunctionalTestCase
{
    private const WEBSERVER_HOST = 'localhost';

    private static int $nextWebServerPort = 8765;
    private int $webServerPort;
    /**
     * @var resource|false
     */
    private $webServerProcess;

    public function setUp(): void
    {
        parent::setUp();

        $this->webServerPort = self::$nextWebServerPort++;

        $this->startWebServer();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->stopWebServer();
    }

    public function testCypressPassesAllSpecsWhenRunDirectly(): void
    {
        $packageRoot = dirname(__DIR__, 2);
        $cypressProjectDir = $packageRoot . '/tests/Functional/Fixtures/MyTestApp/test';

        $command = sprintf(
            '%s/node_modules/.bin/cypress run --project %s --config baseUrl=%s --env tappetApiBaseUrl=%s 2>&1',
            escapeshellarg($packageRoot),
            escapeshellarg($cypressProjectDir),
            'http://localhost:' . $this->webServerPort,
            'http://localhost:' . $this->webServerPort,
        );

        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);
        $allOutput = implode("\n", $output);

        static::assertSame(
            0,
            $exitCode,
            sprintf(
                "Cypress exited with code %d.\nOutput:\n%s",
                $exitCode,
                $allOutput,
            ),
        );
        static::assertStringContainsString('example.spec.php', $allOutput);
        static::assertStringContainsString('All specs passed!', $allOutput);
        static::assertStringNotContainsString(' 0 passing', $allOutput);
    }

    public function testCypressRunsAllScenariosWhenFilterMatchesModuleDescription(): void
    {
        $packageRoot = dirname(__DIR__, 2);
        $command = sprintf(
            '%s/vendor/bin/tappet --project %s run my-suite --base-url=%s --api-base-url %s --api-key test-api-key --filter %s 2>&1',
            escapeshellarg($packageRoot),
            escapeshellarg($packageRoot . '/tests/Functional/Fixtures/MyTestApp/test'),
            escapeshellarg('http://localhost:' . $this->webServerPort),
            escapeshellarg('http://localhost:' . $this->webServerPort),
            escapeshellarg('User Management'),
        );

        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);
        $allOutput = implode("\n", $output);

        static::assertSame(
            0,
            $exitCode,
            sprintf(
                "Cypress exited with code %d.\nOutput:\n%s",
                $exitCode,
                $allOutput,
            ),
        );
        // All five scenarios should run and pass because the module description matches the filter.
        static::assertStringContainsString('5 passing', $allOutput);
        static::assertStringNotContainsString('pending', $allOutput);
    }

    public function testCypressSkipsNonMatchingScenariosWhenFilterMatchesOnlyScenarioDescription(): void
    {
        $packageRoot = dirname(__DIR__, 2);
        // The filter "@mytag" matches "first name can be changed @mytag" but not "last name can be changed".
        $command = sprintf(
            '%s/vendor/bin/tappet --project %s run my-suite --base-url=%s --api-base-url %s --api-key test-api-key --filter %s 2>&1',
            escapeshellarg($packageRoot),
            escapeshellarg($packageRoot . '/tests/Functional/Fixtures/MyTestApp/test'),
            escapeshellarg('http://localhost:' . $this->webServerPort),
            escapeshellarg('http://localhost:' . $this->webServerPort),
            escapeshellarg('@mytag'),
        );

        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);
        $allOutput = implode("\n", $output);

        static::assertSame(
            0,
            $exitCode,
            sprintf(
                "Cypress exited with code %d.\nOutput:\n%s",
                $exitCode,
                $allOutput,
            ),
        );
        // Only the scenario whose description matches the filter should run.
        static::assertStringContainsString('1 passing', $allOutput);
        // The four non-matching scenarios should be registered as skipped (pending).
        static::assertStringContainsString('4 pending', $allOutput);
    }

    public function testCypressSkipsAllScenariosWhenFilterMatchesNeither(): void
    {
        $packageRoot = dirname(__DIR__, 2);
        $command = sprintf(
            '%s/vendor/bin/tappet --project %s run my-suite --base-url=%s --api-base-url %s --api-key test-api-key --filter %s 2>&1',
            escapeshellarg($packageRoot),
            escapeshellarg($packageRoot . '/tests/Functional/Fixtures/MyTestApp/test'),
            escapeshellarg('http://localhost:' . $this->webServerPort),
            escapeshellarg('http://localhost:' . $this->webServerPort),
            escapeshellarg('nonexistent'),
        );

        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);
        $allOutput = implode("\n", $output);

        // Cypress should exit successfully even when all scenarios are skipped.
        static::assertSame(
            0,
            $exitCode,
            sprintf(
                "Cypress exited with code %d.\nOutput:\n%s",
                $exitCode,
                $allOutput,
            ),
        );
        // All five scenarios should be skipped (pending) since nothing matches the filter.
        static::assertStringContainsString('5 pending', $allOutput);
    }

    public function testCypressFailsWhenPageNavigatesUnexpectedlyDuringActStage(): void
    {
        $packageRoot = dirname(__DIR__, 2);
        $cypressProjectDir = $packageRoot . '/tests/Functional/Fixtures/MyTestApp/test';

        // The failure spec navigates away from the edit page (via the cancel interaction) without
        // declaring a new expected page with ExpectNewPage or Visit. The subsequent Type action
        // triggers an assertCurrentPage() which fails because the browser is on the user list
        // page, not the edit page that was set as the current page by OpenPage.
        //
        // The failure spec lives in failure-spec/ (not spec/) so that the normal "all specs passed"
        // test run does not include it. We override specPattern here to run only the failure specs.
        $command = sprintf(
            '%s/node_modules/.bin/cypress run --project %s --config baseUrl=%s,specPattern=failure-spec/unexpected_navigation.spec.php --env tappetApiBaseUrl=%s 2>&1',
            escapeshellarg($packageRoot),
            escapeshellarg($cypressProjectDir),
            'http://localhost:' . $this->webServerPort,
            'http://localhost:' . $this->webServerPort,
        );

        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);
        $allOutput = implode("\n", $output);

        static::assertNotSame(
            0,
            $exitCode,
            sprintf(
                "Cypress should have exited with a non-zero code (test was expected to fail).\nOutput:\n%s",
                $allOutput,
            ),
        );
        static::assertStringContainsString('unexpected_navigation.spec.php', $allOutput);
        static::assertStringContainsString('1 failing', $allOutput);
    }

    public function testCypressFailsWhenModalOpensUnexpectedly(): void
    {
        $packageRoot = dirname(__DIR__, 2);
        $cypressProjectDir = $packageRoot . '/tests/Functional/Fixtures/MyTestApp/test';

        $command = sprintf(
            '%s/node_modules/.bin/cypress run --project %s --config baseUrl=%s,specPattern=failure-spec/modal_opens_unexpectedly.spec.php --env tappetApiBaseUrl=%s,tappetSuite=my-suite 2>&1',
            escapeshellarg($packageRoot),
            escapeshellarg($cypressProjectDir),
            'http://localhost:' . $this->webServerPort,
            'http://localhost:' . $this->webServerPort,
        );

        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);
        $allOutput = implode("\n", $output);

        static::assertNotSame(
            0,
            $exitCode,
            sprintf(
                "Cypress should have exited with a non-zero code (test was expected to fail).\nOutput:\n%s",
                $allOutput,
            ),
        );
        static::assertStringContainsString('modal_opens_unexpectedly.spec.php', $allOutput);
        static::assertStringContainsString('Expected transition log to be empty', $allOutput);
        static::assertStringContainsString('modal "add-user" opening', $allOutput);
        static::assertStringContainsString('1 failing', $allOutput);
    }

    public function testCypressFailsWhenExpectedModalOpenTransitionIsNeverDetected(): void
    {
        $packageRoot = dirname(__DIR__, 2);
        $cypressProjectDir = $packageRoot . '/tests/Functional/Fixtures/MyTestApp/test';

        $command = sprintf(
            '%s/node_modules/.bin/cypress run --project %s --config baseUrl=%s,specPattern=failure-spec/modal_open_transition_not_detected.spec.php --env tappetApiBaseUrl=%s,tappetSuite=my-suite 2>&1',
            escapeshellarg($packageRoot),
            escapeshellarg($cypressProjectDir),
            'http://localhost:' . $this->webServerPort,
            'http://localhost:' . $this->webServerPort,
        );

        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);
        $allOutput = implode("\n", $output);

        static::assertNotSame(
            0,
            $exitCode,
            sprintf(
                "Cypress should have exited with a non-zero code (test was expected to fail).\nOutput:\n%s",
                $allOutput,
            ),
        );
        static::assertStringContainsString('modal_open_transition_not_detected.spec.php', $allOutput);
        static::assertStringContainsString('Waiting for modal "add-user" opening', $allOutput);
        static::assertStringContainsString('1 failing', $allOutput);
    }

    public function testCypressFailsWhenModalClosesUnexpectedly(): void
    {
        $packageRoot = dirname(__DIR__, 2);
        $cypressProjectDir = $packageRoot . '/tests/Functional/Fixtures/MyTestApp/test';

        $command = sprintf(
            '%s/node_modules/.bin/cypress run --project %s --config baseUrl=%s,specPattern=failure-spec/modal_closes_unexpectedly.spec.php --env tappetApiBaseUrl=%s,tappetSuite=my-suite 2>&1',
            escapeshellarg($packageRoot),
            escapeshellarg($cypressProjectDir),
            'http://localhost:' . $this->webServerPort,
            'http://localhost:' . $this->webServerPort,
        );

        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);
        $allOutput = implode("\n", $output);

        static::assertNotSame(
            0,
            $exitCode,
            sprintf(
                "Cypress should have exited with a non-zero code (test was expected to fail).\nOutput:\n%s",
                $allOutput,
            ),
        );
        static::assertStringContainsString('modal_closes_unexpectedly.spec.php', $allOutput);
        static::assertStringContainsString('Expected transition log to be empty', $allOutput);
        static::assertStringContainsString('modal "add-user" closing', $allOutput);
        static::assertStringContainsString('1 failing', $allOutput);
    }

    public function testCypressPurgesDeferredPurgeFixtureOnlyOnceTheWholeRunHasExited(): void
    {
        $packageRoot = dirname(__DIR__, 2);
        $cypressProjectDir = $packageRoot . '/tests/Functional/Fixtures/MyTestApp/test';

        // Isolated in deferred-purge-spec/ (see that spec file's own comment) rather than
        // spec/, so it doesn't affect the scenario-count assertions the other tests here make
        // against the default specPattern.
        $command = sprintf(
            '%s/node_modules/.bin/cypress run --project %s --config baseUrl=%s,specPattern=deferred-purge-spec/**/*.spec.php --env tappetApiBaseUrl=%s 2>&1',
            escapeshellarg($packageRoot),
            escapeshellarg($cypressProjectDir),
            'http://localhost:' . $this->webServerPort,
            'http://localhost:' . $this->webServerPort,
        );

        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);
        $allOutput = implode("\n", $output);

        static::assertSame(
            0,
            $exitCode,
            sprintf(
                "Cypress exited with code %d.\nOutput:\n%s",
                $exitCode,
                $allOutput,
            ),
        );
        static::assertStringContainsString('deferred_purge.spec.php', $allOutput);
        static::assertStringContainsString('All specs passed!', $allOutput);
        static::assertStringNotContainsString(' 0 passing', $allOutput);

        /*
         * The deferred-purge fixture's model must have been purged now that the whole Cypress
         * run's Node.js controller process has exited - its "after:run" handler (src/ts/cypress/
         * plugin/index.ts) sends the deferred DELETE request as the process drains, which keeps
         * the event loop alive until that request settles, so it's expected to have completed by
         * the time exec() above returns (the "cypress run" CLI process only exits once its own
         * Node.js event loop is fully drained).
         */
        $usersPageHtml = file_get_contents(
            'http://' . self::WEBSERVER_HOST . ':' . $this->webServerPort . '/users',
        );

        static::assertIsString($usersPageHtml);
        static::assertStringNotContainsString('Persistent User', $usersPageHtml);
    }

    public function testCypressPassesAllSpecsWhenRunViaTappetBinary(): void
    {
        $packageRoot = dirname(__DIR__, 2);

        $command = sprintf(
            '%s/vendor/bin/tappet --project %s run my-suite --base-url=%s --api-base-url %s --api-key test-api-key 2>&1',
            escapeshellarg($packageRoot),
            escapeshellarg($packageRoot . '/tests/Functional/Fixtures/MyTestApp/test'),
            escapeshellarg('http://localhost:' . $this->webServerPort),
            escapeshellarg('http://localhost:' . $this->webServerPort),
        );

        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);
        $allOutput = implode("\n", $output);

        static::assertSame(
            0,
            $exitCode,
            sprintf(
                "Tappet binary exited with code %d.\nOutput:\n%s",
                $exitCode,
                $allOutput,
            ),
        );
        static::assertStringContainsString('example.spec.php', $allOutput);
        static::assertStringContainsString('All specs passed!', $allOutput);
        static::assertStringNotContainsString(' 0 passing', $allOutput);
    }

    /**
     * Deletes the fixture app's PHP session file (fixed session ID "tappet-test", shared across the
     * cookie-less cy.task(...) HTTP calls - see web/index.php) so that state from a previous run of this
     * test suite (e.g. previously loaded users) cannot leak into this run's assertions.
     */
    private function clearStaleSession(): void
    {
        $sessionFile = (session_save_path() ?: sys_get_temp_dir()) . '/sess_tappet-test';

        if (file_exists($sessionFile)) {
            unlink($sessionFile);
        }
    }

    private function startWebServer(): void
    {
        $this->clearStaleSession();

        $packageRoot = dirname(__DIR__, 2);
        $docRoot = $packageRoot . '/tests/Functional/Fixtures/MyTestApp/web';
        $router = $docRoot . '/index.php';

        $command = sprintf(
            'php -S %s:%d -d opcache.jit=disable %s',
            self::WEBSERVER_HOST,
            $this->webServerPort,
            escapeshellarg($router),
        );

        // Capture stderr so we can block until the server signals it is ready.
        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['file', '/dev/null', 'w'],
            2 => ['pipe', 'w'],
        ];

        $this->webServerProcess = proc_open($command, $descriptorSpec, $pipes, $packageRoot);

        if ($this->webServerProcess === false) {
            $this->fail('Failed to start PHP built-in web server');
        }

        fclose($pipes[0]);

        // The built-in server writes "[date] PHP x.x Development Server (...) started"
        // to stderr the moment it is bound and ready to accept connections.
        // Block here until that line arrives.
        $startupLine = fgets($pipes[2]);
        fclose($pipes[2]);

        if ($startupLine === false || !str_contains($startupLine, 'started')) {
            $this->fail(
                'PHP built-in web server did not start correctly: ' .
                ($startupLine !== false ? rtrim($startupLine) : '(no output)'),
            );
        }
    }

    private function stopWebServer(): void
    {
        if ($this->webServerProcess !== false) {
            proc_terminate($this->webServerProcess, SIGTERM);
            proc_close($this->webServerProcess);

            $this->webServerProcess = false;
        }
    }
}
