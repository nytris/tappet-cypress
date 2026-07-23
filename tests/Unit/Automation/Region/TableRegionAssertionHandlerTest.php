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

namespace Tappet\Cypress\Tests\Unit\Automation\Region;

use Mockery;
use Mockery\MockInterface;
use Tappet\Cypress\Automation\CypressAutomationInterface;
use Tappet\Cypress\Automation\Matcher\Context;
use Tappet\Cypress\Automation\Matcher\ContextInterface;
use Tappet\Cypress\Automation\Region\TableRegionAssertionHandler;
use Tappet\Cypress\Tests\AbstractTestCase;
use Tappet\Runner\Automation\Matcher\MatcherRegistryInterface;
use Tappet\Runner\Standard\Assertion\ExpectTable;
use Tappet\Runner\Standard\Matcher\Text;

/**
 * Class TableRegionAssertionHandlerTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class TableRegionAssertionHandlerTest extends AbstractTestCase
{
    /**
     * A Uniter FFI wrapper of Cypress's cy global, stubbed as an anonymous mock.
     */
    private mixed $cy;
    private CypressAutomationInterface&MockInterface $automation;
    private TableRegionAssertionHandler $handler;
    /**
     * @var MatcherRegistryInterface<ContextInterface>&MockInterface
     */
    private MatcherRegistryInterface&MockInterface $matcherRegistry;

    public function setUp(): void
    {
        parent::setUp();

        $this->cy = mock();
        $this->matcherRegistry = mock(MatcherRegistryInterface::class);
        $this->automation = mock(CypressAutomationInterface::class, [
            'getCy' => $this->cy,
            'getAttributePrefix' => 'ui',
        ]);

        $this->handler = new TableRegionAssertionHandler($this->automation, $this->matcherRegistry);
    }

    public function testGetHandlersMapsExpectTableClassToCallable(): void
    {
        $handlers = $this->handler->getHandlers();

        static::assertArrayHasKey(ExpectTable::class, $handlers);
        static::assertIsCallable($handlers[ExpectTable::class]);
    }

    public function testAssertTableResolvesColumnIndexFromHeadingCellOnceAndMatchesEachRow(): void
    {
        $firstRowMatcher = new Text('Alice');
        $secondRowMatcher = new Text('Bob');
        $assertion = new ExpectTable('users', [
            ['name' => $firstRowMatcher],
            ['name' => $secondRowMatcher],
        ]);
        $headingChain = mock();
        $headingCell = mock(['index' => 0, 'attr' => null]);
        $rowChain = mock();
        $firstCellChain = mock();
        $secondCellChain = mock();

        // The heading cell for the "name" column is only looked up once, regardless of row count.
        $this->cy->expects()
            ->get('[data-ui-region="users"] [data-ui-column="name"]')
            ->once()
            ->andReturn($headingChain);
        $headingChain->expects()
            ->then(Mockery::on(function (callable $callback) use ($headingCell): bool {
                $callback($headingCell);
                return true;
            }))
            ->once();

        // Row 0's "name" data cell, resolved by the heading's column index (0).
        $this->cy->expects()
            ->get('[data-ui-region="users"] tbody tr')
            ->twice()
            ->andReturn($rowChain);
        $rowChain->expects()
            ->eq(0)
            ->once()
            ->andReturn($rowChain);
        $rowChain->expects()
            ->find('td')
            ->twice()
            ->andReturn($firstCellChain, $secondCellChain);
        $firstCellChain->expects()
            ->eq(0)
            ->once()
            ->andReturn($firstCellChain);
        $this->matcherRegistry->expects()
            ->handleMatcher(
                'default',
                $firstRowMatcher,
                Mockery::on(fn (Context $context): bool => $context->getTarget() === $firstCellChain)
            )
            ->once();

        // Row 1's "name" data cell, resolved via the same column index.
        $rowChain->expects()
            ->eq(1)
            ->once()
            ->andReturn($rowChain);
        $secondCellChain->expects()
            ->eq(0)
            ->once()
            ->andReturn($secondCellChain);
        $this->matcherRegistry->expects()
            ->handleMatcher(
                'default',
                $secondRowMatcher,
                Mockery::on(fn (Context $context): bool => $context->getTarget() === $secondCellChain)
            )
            ->once();

        $this->handler->getHandlers()[ExpectTable::class]($assertion);
    }

    public function testAssertTableResolvesEachColumnAgainstItsOwnHeadingIndex(): void
    {
        $nameMatcher = new Text('Alice');
        $emailMatcher = new Text('alice@example.com');
        $assertion = new ExpectTable('users', [
            ['name' => $nameMatcher, 'email' => $emailMatcher],
        ]);
        $nameHeadingChain = mock();
        $nameHeadingCell = mock(['index' => 0, 'attr' => null]);
        $emailHeadingChain = mock();
        $emailHeadingCell = mock(['index' => 1, 'attr' => null]);
        $rowChain = mock();
        $nameCellChain = mock();
        $emailCellChain = mock();

        $this->cy->expects()
            ->get('[data-ui-region="users"] [data-ui-column="name"]')
            ->once()
            ->andReturn($nameHeadingChain);
        $nameHeadingChain->expects()
            ->then(Mockery::on(function (callable $callback) use ($nameHeadingCell): bool {
                $callback($nameHeadingCell);
                return true;
            }))
            ->once();
        $this->cy->expects()
            ->get('[data-ui-region="users"] [data-ui-column="email"]')
            ->once()
            ->andReturn($emailHeadingChain);
        $emailHeadingChain->expects()
            ->then(Mockery::on(function (callable $callback) use ($emailHeadingCell): bool {
                $callback($emailHeadingCell);
                return true;
            }))
            ->once();

        $this->cy->expects()
            ->get('[data-ui-region="users"] tbody tr')
            ->twice()
            ->andReturn($rowChain);
        $rowChain->expects()
            ->eq(0)
            ->twice()
            ->andReturn($rowChain);
        $rowChain->expects()
            ->find('td')
            ->twice()
            ->andReturn($nameCellChain, $emailCellChain);
        $nameCellChain->expects()
            ->eq(0)
            ->once()
            ->andReturn($nameCellChain);
        $this->matcherRegistry->expects()
            ->handleMatcher(
                'default',
                $nameMatcher,
                Mockery::on(fn (Context $context): bool => $context->getTarget() === $nameCellChain)
            )
            ->once();
        $emailCellChain->expects()
            ->eq(1)
            ->once()
            ->andReturn($emailCellChain);
        $this->matcherRegistry->expects()
            ->handleMatcher(
                'default',
                $emailMatcher,
                Mockery::on(fn (Context $context): bool => $context->getTarget() === $emailCellChain)
            )
            ->once();

        $this->handler->getHandlers()[ExpectTable::class]($assertion);
    }

    public function testAssertTableUsesMatchTypeFromHeadingCellAttribute(): void
    {
        $statusMatcher = new Text('active');
        $assertion = new ExpectTable('users', [
            ['status' => $statusMatcher],
        ]);
        $headingChain = mock();
        $headingCell = mock(['index' => 0, 'attr' => 'badge']);
        $rowChain = mock();
        $cellChain = mock();

        $this->cy->expects()
            ->get('[data-ui-region="users"] [data-ui-column="status"]')
            ->once()
            ->andReturn($headingChain);
        $headingChain->expects()
            ->then(Mockery::on(function (callable $callback) use ($headingCell): bool {
                $callback($headingCell);
                return true;
            }))
            ->once();
        $this->cy->expects()
            ->get('[data-ui-region="users"] tbody tr')
            ->once()
            ->andReturn($rowChain);
        $rowChain->expects()->eq(0)->once()->andReturn($rowChain);
        $rowChain->expects()->find('td')->once()->andReturn($cellChain);
        $cellChain->expects()->eq(0)->once()->andReturn($cellChain);
        $this->matcherRegistry->expects()
            ->handleMatcher(
                'badge',
                $statusMatcher,
                Mockery::on(fn (Context $context): bool => $context->getTarget() === $cellChain)
            )
            ->once();

        $this->handler->getHandlers()[ExpectTable::class]($assertion);
    }

    public function testAssertTableUsesConfiguredAttributePrefix(): void
    {
        $nameMatcher = new Text('Alice');
        $assertion = new ExpectTable('users', [
            ['name' => $nameMatcher],
        ]);
        $automation = mock(CypressAutomationInterface::class, [
            'getCy' => $this->cy,
            'getAttributePrefix' => 'my-app',
        ]);
        $handler = new TableRegionAssertionHandler($automation, $this->matcherRegistry);
        $headingChain = mock();
        $headingCell = mock(['index' => 0, 'attr' => null]);
        $rowChain = mock();
        $cellChain = mock();

        $this->cy->expects()
            ->get('[data-my-app-region="users"] [data-my-app-column="name"]')
            ->once()
            ->andReturn($headingChain);
        $headingChain->expects()
            ->then(Mockery::on(function (callable $callback) use ($headingCell): bool {
                $callback($headingCell);
                return true;
            }))
            ->once();
        $this->cy->expects()
            ->get('[data-my-app-region="users"] tbody tr')
            ->once()
            ->andReturn($rowChain);
        $rowChain->expects()->eq(0)->once()->andReturn($rowChain);
        $rowChain->expects()->find('td')->once()->andReturn($cellChain);
        $cellChain->expects()->eq(0)->once()->andReturn($cellChain);
        $this->matcherRegistry->expects()
            ->handleMatcher(
                'default',
                $nameMatcher,
                Mockery::on(fn (Context $context): bool => $context->getTarget() === $cellChain)
            )
            ->once();

        $handler->getHandlers()[ExpectTable::class]($assertion);
    }
}
