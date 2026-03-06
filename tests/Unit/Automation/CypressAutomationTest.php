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

namespace Tappet\Cypress\Tests\Unit\Automation;

use Tappet\Cypress\Automation\CypressAutomation;
use Tappet\Cypress\Tests\AbstractTestCase;

/**
 * Class CypressAutomationTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class CypressAutomationTest extends AbstractTestCase
{
    // $cy is a Uniter FFI wrapper of Cypress's cy global; stub as an anonymous mock.
    private mixed $cy;
    private CypressAutomation $automation;

    public function setUp(): void
    {
        parent::setUp();

        $this->cy = mock();

        $this->automation = new CypressAutomation($this->cy);
    }

    public function testAssertPageCallsCyUrlThenShould(): void
    {
        $urlChain = mock();
        $urlChain->expects()
            ->should('eq', 'https://example.com/dashboard')
            ->once();
        $this->cy->expects()
            ->url()
            ->once()
            ->andReturn($urlChain);

        $this->automation->assertPage('https://example.com/dashboard');
    }

    public function testTypeFieldCallsCyGetWithDataTappetFieldSelector(): void
    {
        $getChain = mock();
        $getChain->expects()
            ->type('hello world')
            ->once();
        $this->cy->expects()
            ->get('[data-tappet-field="username"]')
            ->once()
            ->andReturn($getChain);

        $this->automation->typeField('username', 'hello world');
    }

    public function testTypeFieldUsesCorrectSelectorForDifferentHandle(): void
    {
        $getChain = mock();
        $getChain->expects()
            ->type('secret')
            ->once();
        $this->cy->expects()
            ->get('[data-tappet-field="password"]')
            ->once()
            ->andReturn($getChain);

        $this->automation->typeField('password', 'secret');
    }

    public function testVisitPageCallsCyVisit(): void
    {
        $this->cy->expects()
            ->visit('https://example.com/login')
            ->once();

        $this->automation->visitPage('https://example.com/login');
    }
}
