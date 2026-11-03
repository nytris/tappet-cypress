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

namespace Tappet\Cypress\Tests\Functional\Fixtures\SymfonyTestApp\src\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class WidgetController.
 *
 * Renders a widget's page. Deliberately never renders the widget's secret code -
 * that value is only ever fetchable via the Tappet API's remote state endpoint.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class WidgetController
{
    public function __invoke(int $id): Response
    {
        $widget = $_SESSION['widgets'][$id] ?? null;

        if ($widget === null) {
            throw new NotFoundHttpException('Widget not found');
        }

        return new Response(
            '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>Widget</title></head><body>' .
            '<div data-ui-region="widget-name">' . htmlspecialchars($widget['name']) . '</div>' .
            '</body></html>'
        );
    }
}
