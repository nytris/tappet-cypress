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

namespace Tappet\Cypress\Tests\Functional\Fixtures\SymfonyTestApp\src\FixtureLoader;

use Tappet\Api\Fixture\Loader\DelegatingFixtureLoader;
use Tappet\Api\Fixture\Loader\FixtureLoaderInterface;
use Tappet\Api\Fixture\Loader\LoaderPair;
use Tappet\Bundle\DependencyInjection\Compiler\RegisterFixtureLoadersPass;
use Tappet\Bundle\DependencyInjection\TappetExtension;
use Tappet\Cypress\Tests\Functional\Fixtures\SymfonyTestApp\test\app\Fixture\WidgetFixture;
use Tappet\Cypress\Tests\Functional\Fixtures\SymfonyTestApp\test\app\Fixture\WidgetModel;

/**
 * Class WidgetFixtureLoader.
 *
 * A custom fixture loader, autoconfigured as a Symfony service and auto-tagged by
 * {@see TappetExtension} (it implements {@see FixtureLoaderInterface}) so that {@see RegisterFixtureLoadersPass}
 * wires it into {@see DelegatingFixtureLoader} - proving fixtures loaded via a GUI test scenario
 * really do reach a fixture loader registered against the real tappet/bundle.
 *
 * @implements FixtureLoaderInterface<WidgetFixture, WidgetModel>
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class WidgetFixtureLoader implements FixtureLoaderInterface
{
    /**
     * @inheritDoc
     */
    public function getLoaderPairs(): array
    {
        return [
            WidgetFixture::class => new LoaderPair(
                function (WidgetFixture $fixture): WidgetModel {
                    $id = ($_SESSION['next_widget_id'] ?? 1);
                    $_SESSION['next_widget_id'] = $id + 1;

                    $_SESSION['widgets'][$id] = [
                        'id' => $id,
                        'name' => $fixture->getName(),
                        // Deliberately never rendered by WidgetController - only fetchable via
                        // the Tappet API's remote state endpoint, for use by a remote state assertion.
                        'secret_code' => $fixture->getSecretCode(),
                    ];
                    $_SESSION['last_loaded_widget_id'] = $id;

                    return new WidgetModel($id);
                },
                function (WidgetFixture $fixture, WidgetModel $model): void {
                    unset($_SESSION['widgets'][$model->getId()]);
                },
            ),
        ];
    }
}
