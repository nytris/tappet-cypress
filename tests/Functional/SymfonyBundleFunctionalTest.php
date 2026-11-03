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
 * Class SymfonyBundleFunctionalTest.
 *
 * Starts a real Symfony application (`Fixtures/SymfonyTestApp`) with tappet/bundle installed
 * and configured with its own custom FixtureLoaderInterface and RemoteStateProviderInterface
 * implementations, runs Cypress against it, and asserts that all Cypress tests pass.
 *
 * Unlike CypressFunctionalTest (which exercises MyTestApp's hand-rolled plain-PHP routes that
 * merely mimic tappet/bundle's API shape), this proves the three projects genuinely work
 * together end-to-end: this Cypress adapter -> tappet/bundle's real HTTP endpoints
 * (FixtureController, RemoteStateController, their compiler passes and auto-registered
 * routes) -> tappet/tappet's core registries.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class SymfonyBundleFunctionalTest extends AbstractFunctionalTestCase
{
    private const WEBSERVER_HOST = 'localhost';
    // Distinct range from CypressFunctionalTest's (starting at 8765) so the two test classes'
    // web servers can never collide even if a future change makes them run concurrently.
    private static int $nextWebServerPort = 8865;
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

    public function testCypressPassesAllSpecsAgainstTheRealTappetSymfonyBundle(): void
    {
        $packageRoot = dirname(__DIR__, 2);
        $cypressProjectDir = $packageRoot . '/tests/Functional/Fixtures/SymfonyTestApp/test';

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
        static::assertStringContainsString('widget.spec.php', $allOutput);
        static::assertStringContainsString('All specs passed!', $allOutput);
        static::assertStringNotContainsString(' 0 passing', $allOutput);
    }

    private function clearStaleSession(): void
    {
        $sessionFile = (session_save_path() ?: sys_get_temp_dir()) . '/sess_tappet-test-symfony';

        if (file_exists($sessionFile)) {
            unlink($sessionFile);
        }
    }

    private function startWebServer(): void
    {
        $this->clearStaleSession();

        $packageRoot = dirname(__DIR__, 2);
        $docRoot = $packageRoot . '/tests/Functional/Fixtures/SymfonyTestApp/web';
        $router = $docRoot . '/app.php';

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
