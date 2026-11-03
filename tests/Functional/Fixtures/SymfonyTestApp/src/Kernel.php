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

namespace Tappet\Cypress\Tests\Functional\Fixtures\SymfonyTestApp\src;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Tappet\Bundle\TappetBundle;

/**
 * Class Kernel.
 *
 * A minimal real Symfony application, with {@see TappetBundle} registered, used to prove the whole
 * stack (this Cypress adapter -> tappet/bundle's HTTP endpoints -> tappet/tappet's core
 * registries) works together end-to-end.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class Kernel extends BaseKernel
{
    /**
     * @inheritDoc
     */
    public function getCacheDir(): string
    {
        return $this->getPackageVarDir() . '/cache';
    }

    /**
     * @inheritDoc
     */
    public function getLogDir(): string
    {
        return $this->getPackageVarDir() . '/logs';
    }

    /**
     * Fetches this app's own subdirectory of the package's existing (.gitignored) root `var/`
     * directory, rather than creating a new one nested under this fixture app.
     */
    private function getPackageVarDir(): string
    {
        return dirname(__DIR__, 5) . '/var/symfony-test-app/' . $this->environment;
    }

    /**
     * @inheritDoc
     */
    public function getProjectDir(): string
    {
        return dirname(__DIR__);
    }

    /**
     * @inheritDoc
     */
    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new TappetBundle(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load($this->getProjectDir() . '/config/config_' . $this->getEnvironment() . '.yml');
    }
}
