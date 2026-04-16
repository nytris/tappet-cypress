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

use Tappet\Core\Assertion\RegionAssertionInterface;
use Tappet\Core\Automation\AutomationInterface;
use Tappet\Core\Automation\Region\RegionAssertionHandlerInterface;
use Tappet\Core\Standard\Assertion\ExpectRegionContains;
use Tappet\Core\Standard\Assertion\ExpectRegionDoesNotContain;
use Tappet\Cypress\Automation\CypressAutomation;

/**
 * Class TextRegionAssertionHandler.
 *
 * Handles assertions on text regions.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class TextRegionAssertionHandler implements RegionAssertionHandlerInterface
{
    /**
     * @inheritDoc
     */
    public function getHandlers(): array
    {
        return [
            ExpectRegionContains::class => function (RegionAssertionInterface $assertion, AutomationInterface $automation): void {
                /** @var ExpectRegionContains $assertion */
                /** @var CypressAutomation $automation */
                $this->assertRegionContains($assertion, $automation);
            },
            ExpectRegionDoesNotContain::class => function (RegionAssertionInterface $assertion, AutomationInterface $automation): void {
                /** @var ExpectRegionDoesNotContain $assertion */
                /** @var CypressAutomation $automation */
                $this->assertRegionDoesNotContain($assertion, $automation);
            },
        ];
    }

    /**
     * Asserts that the specified text is contained in the region.
     */
    public function assertRegionContains(ExpectRegionContains $assertion, CypressAutomation $automation): void
    {
        $attributePrefix = $automation->getAttributePrefix();
        $cy = $automation->getCy();

        $cy->get('[data-' . $attributePrefix . '-region="' . $assertion->getRegionHandle() . '"]')->should('contain', $assertion->getText());
    }

    /**
     * Asserts that the specified text is not contained in the region.
     */
    public function assertRegionDoesNotContain(ExpectRegionDoesNotContain $assertion, CypressAutomation $automation): void
    {
        $attributePrefix = $automation->getAttributePrefix();
        $cy = $automation->getCy();

        $cy->get('[data-' . $attributePrefix . '-region="' . $assertion->getRegionHandle() . '"]')->should('not.contain', $assertion->getText());
    }
}
