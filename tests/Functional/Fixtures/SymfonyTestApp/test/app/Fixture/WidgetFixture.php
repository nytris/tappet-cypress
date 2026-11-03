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

namespace Tappet\Cypress\Tests\Functional\Fixtures\SymfonyTestApp\test\app\Fixture;

use Tappet\Common\Fixture\AbstractFixture;

/**
 * @extends AbstractFixture<WidgetModel>
 */
class WidgetFixture extends AbstractFixture
{
    public function __construct(
        private readonly string $name,
        private readonly string $secretCode
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Fetches the widget's secret code - deliberately never rendered anywhere in the UI,
     * only fetchable via the Tappet API's remote state endpoint.
     */
    public function getSecretCode(): string
    {
        return $this->secretCode;
    }

    public static function getModelClass(): string
    {
        return WidgetModel::class;
    }
}
