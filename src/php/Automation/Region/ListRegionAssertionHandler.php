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
use Tappet\Runner\Standard\Assertion\ExpectList;

/**
 * Class ListRegionAssertionHandler.
 *
 * Handles assertions on list regions.
 *
 * @implements RegionAssertionHandlerInterface<RegionAssertionInterface>
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class ListRegionAssertionHandler implements RegionAssertionHandlerInterface
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
            ExpectList::class => function (RegionAssertionInterface $assertion): void {
                /** @var ExpectList $assertion */
                $this->assertList($assertion);
            },
        ];
    }

    /**
     * Asserts that the list contains the expected items in order.
     *
     * The region's own `data-*-match-type` attribute (defaulting to "default" if absent) determines
     * which matcher handler type is used to match each of its items.
     */
    public function assertList(ExpectList $assertion): void
    {
        $attributePrefix = $this->automation->getAttributePrefix();
        $cy = $this->automation->getCy();
        $matcherRegistry = $this->matcherRegistry;
        $items = $assertion->getItems();
        $regionSelector = '[data-' . $attributePrefix . '-region="' . $assertion->getRegionHandle() . '"]';

        $cy->get($regionSelector)
            ->then(function ($region) use ($cy, $attributePrefix, $matcherRegistry, $items, $regionSelector): void {
                $matchType = $region->attr('data-' . $attributePrefix . '-match-type') ?: 'default';

                foreach ($items as $index => $matcher) {
                    $item = $cy->get($regionSelector . ' li')->eq($index);

                    $matcherRegistry->handleMatcher($matchType, $matcher, new Context($item));
                }
            });
    }
}
