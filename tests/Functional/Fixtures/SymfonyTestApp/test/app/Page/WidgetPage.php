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

namespace Tappet\Cypress\Tests\Functional\Fixtures\SymfonyTestApp\test\app\Page;

use Tappet\Cypress\Tests\Functional\Fixtures\SymfonyTestApp\test\app\Fixture\WidgetModel;
use Tappet\Runner\Environment\EnvironmentInterface;
use Tappet\Runner\Page\PageInterface;

class WidgetPage implements PageInterface
{
    public function __construct(
        private readonly string $widgetFixtureHandle
    ) {
    }

    public function buildUrl(EnvironmentInterface $environment): string
    {
        return '/widgets/' . $environment->getFixtureModel(WidgetModel::class, $this->widgetFixtureHandle)->getId();
    }
}
