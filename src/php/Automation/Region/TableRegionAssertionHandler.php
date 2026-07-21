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

namespace Tappet\Cypress\Automation\Region;

use Tappet\Cypress\Automation\CypressAutomationInterface;
use Tappet\Cypress\Automation\Matcher\Context;
use Tappet\Cypress\Automation\Matcher\ContextInterface;
use Tappet\Runner\Assertion\RegionAssertionInterface;
use Tappet\Runner\Automation\Matcher\MatcherRegistryInterface;
use Tappet\Runner\Automation\Region\RegionAssertionHandlerInterface;
use Tappet\Runner\Standard\Assertion\ExpectTable;

/**
 * Class TableRegionAssertionHandler.
 *
 * Handles assertions on table regions.
 *
 * @implements RegionAssertionHandlerInterface<RegionAssertionInterface>
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class TableRegionAssertionHandler implements RegionAssertionHandlerInterface
{
    /**
     * @param MatcherRegistryInterface<ContextInterface> $matcherRegistry
     */
    public function __construct(
        private readonly CypressAutomationInterface $automation,
        private readonly MatcherRegistryInterface $matcherRegistry
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getHandlers(): array
    {
        return [
            ExpectTable::class => function (RegionAssertionInterface $assertion): void {
                /** @var ExpectTable $assertion */
                $this->assertTable($assertion);
            },
        ];
    }

    /**
     * Asserts that the table contains the expected rows and column values.
     *
     * A `data-*-column` attribute is specified on the heading cell (`<th>` or `<td>`) for each column,
     * with its position among its heading row siblings used to locate the corresponding `<td>`
     * to match within each `<tbody>` row. The same heading cell's `data-*-match-type` attribute
     * (defaulting to "default" if absent) determines which matcher handler
     * type is used to match each of that column's data cells.
     */
    public function assertTable(ExpectTable $assertion): void
    {
        $attributePrefix = $this->automation->getAttributePrefix();
        $cy = $this->automation->getCy();
        $matcherRegistry = $this->matcherRegistry;
        $rows = $assertion->getRows();
        $regionSelector = '[data-' . $attributePrefix . '-region="' . $assertion->getRegionHandle() . '"]';

        $columnHandles = [];

        // Resolve all uniquely referenced column handles (each row may assert against only certain columns,
        // not necessarily all).
        foreach ($rows as $row) {
            foreach (array_keys($row) as $columnHandle) {
                $columnHandles[$columnHandle] = true;
            }
        }

        foreach (array_keys($columnHandles) as $columnHandle) {
            $cy->get($regionSelector . ' [data-' . $attributePrefix . '-column="' . $columnHandle . '"]')
                ->then(function ($headingCell) use (
                    $cy,
                    $attributePrefix,
                    $matcherRegistry,
                    $rows,
                    $columnHandle,
                    $regionSelector
                ): void {
                    $columnIndex = $headingCell->index();
                    $matchType = $headingCell->attr('data-' . $attributePrefix . '-match-type') ?: 'default';

                    foreach ($rows as $rowIndex => $row) {
                        if (!array_key_exists($columnHandle, $row)) {
                            continue;
                        }

                        $cell = $cy->get($regionSelector . ' tbody tr')
                            ->eq($rowIndex)
                            ->find('td')
                            ->eq($columnIndex);

                        $matcherRegistry->handleMatcher($matchType, $row[$columnHandle], new Context($cell));
                    }
                });
        }
    }
}
