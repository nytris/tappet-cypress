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
use Tappet\Runner\Assertion\RegionAssertionInterface;
use Tappet\Runner\Automation\Region\RegionAssertionHandlerInterface;
use Tappet\Runner\Standard\Assertion\ExpectRegionContains;
use Tappet\Runner\Standard\Assertion\ExpectRegionDoesNotContain;

/**
 * Class TextRegionAssertionHandler.
 *
 * Handles assertions on text regions.
 *
 * @implements RegionAssertionHandlerInterface<RegionAssertionInterface>
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class TextRegionAssertionHandler implements RegionAssertionHandlerInterface
{
    public function __construct(
        private readonly CypressAutomationInterface $automation
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getHandlers(): array
    {
        return [
            ExpectRegionContains::class => function (RegionAssertionInterface $assertion): void {
                /** @var ExpectRegionContains $assertion */
                $this->assertRegionContains($assertion);
            },
            ExpectRegionDoesNotContain::class => function (RegionAssertionInterface $assertion): void {
                /** @var ExpectRegionDoesNotContain $assertion */
                $this->assertRegionDoesNotContain($assertion);
            },
        ];
    }

    /**
     * Asserts that the specified text is contained in the region.
     */
    public function assertRegionContains(ExpectRegionContains $assertion): void
    {
        $attributePrefix = $this->automation->getAttributePrefix();
        $cy = $this->automation->getCy();

        $cy->get('[data-' . $attributePrefix . '-region="' . $assertion->getRegionHandle() . '"]')->should('contain', $assertion->getText());
    }

    /**
     * Asserts that the specified text is not contained in the region.
     */
    public function assertRegionDoesNotContain(ExpectRegionDoesNotContain $assertion): void
    {
        $attributePrefix = $this->automation->getAttributePrefix();
        $cy = $this->automation->getCy();

        $cy->get('[data-' . $attributePrefix . '-region="' . $assertion->getRegionHandle() . '"]')->should('not.contain', $assertion->getText());
    }
}
