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
        // Both scenarios should run and pass because the module description matches the filter.
        static::assertStringContainsString('2 passing', $allOutput);
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
        // The non-matching scenario should be registered as skipped (pending).
        static::assertStringContainsString('1 pending', $allOutput);
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
        // Both scenarios should be skipped (pending) since nothing matches the filter.
        static::assertStringContainsString('2 pending', $allOutput);
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

    private function startWebServer(): void
    {
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
