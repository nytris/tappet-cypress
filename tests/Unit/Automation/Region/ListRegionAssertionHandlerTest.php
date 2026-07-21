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
use Tappet\Cypress\Automation\Region\ListRegionAssertionHandler;
use Tappet\Cypress\Tests\AbstractTestCase;
use Tappet\Runner\Automation\Matcher\MatcherRegistryInterface;
use Tappet\Runner\Standard\Assertion\ExpectList;
use Tappet\Runner\Standard\Matcher\Text;

/**
 * Class ListRegionAssertionHandlerTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class ListRegionAssertionHandlerTest extends AbstractTestCase
{
    private CypressAutomationInterface&MockInterface $automation;
    /**
     * A Uniter FFI wrapper of Cypress's cy global, stubbed as an anonymous mock.
     */
    private mixed $cy;
    private ListRegionAssertionHandler $handler;
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

        $this->handler = new ListRegionAssertionHandler($this->automation, $this->matcherRegistry);
    }

    public function testGetHandlersMapsExpectListClassToCallable(): void
    {
        $handlers = $this->handler->getHandlers();

        static::assertArrayHasKey(ExpectList::class, $handlers);
        static::assertIsCallable($handlers[ExpectList::class]);
    }

    public function testAssertListAssertsItemsInOrderViaMatcherRegistry(): void
    {
        $firstMatcher = new Text('First item');
        $secondMatcher = new Text('Second item');
        $assertion = new ExpectList('recent-items', [$firstMatcher, $secondMatcher]);
        $regionChain = mock();
        $region = mock(['attr' => null]);
        $itemChain = mock();

        $this->cy->expects()
            ->get('[data-ui-region="recent-items"]')
            ->once()
            ->andReturn($regionChain);
        $regionChain->expects()
            ->then(Mockery::on(function (callable $callback) use ($region): bool {
                $callback($region);
                return true;
            }))
            ->once();

        $this->cy->expects()
            ->get('[data-ui-region="recent-items"] li')
            ->twice()
            ->andReturn($itemChain);
        $itemChain->expects()
            ->eq(0)
            ->once()
            ->andReturn($itemChain);
        $this->matcherRegistry->expects()
            ->handleMatcher(
                'default',
                $firstMatcher,
                Mockery::on(fn (Context $context): bool => $context->getTarget() === $itemChain)
            )
            ->once();
        $itemChain->expects()
            ->eq(1)
            ->once()
            ->andReturn($itemChain);
        $this->matcherRegistry->expects()
            ->handleMatcher(
                'default',
                $secondMatcher,
                Mockery::on(fn (Context $context): bool => $context->getTarget() === $itemChain)
            )
            ->once();

        $this->handler->getHandlers()[ExpectList::class]($assertion);
    }

    public function testAssertListUsesMatchTypeFromRegionAttribute(): void
    {
        $matcher = new Text('First item');
        $assertion = new ExpectList('recent-items', [$matcher]);
        $regionChain = mock();
        $region = mock();
        $itemChain = mock();
        $region->allows()
            ->attr('data-ui-match-type')
            ->andReturn('badge');

        $this->cy->expects()
            ->get('[data-ui-region="recent-items"]')
            ->once()
            ->andReturn($regionChain);
        $regionChain->expects()
            ->then(Mockery::on(function (callable $callback) use ($region): bool {
                $callback($region);
                return true;
            }))
            ->once();

        $this->cy->expects()
            ->get('[data-ui-region="recent-items"] li')
            ->once()
            ->andReturn($itemChain);
        $itemChain->expects()->eq(0)->once()->andReturn($itemChain);
        $this->matcherRegistry->expects()
            ->handleMatcher(
                'badge',
                $matcher,
                Mockery::on(fn (Context $context): bool => $context->getTarget() === $itemChain)
            )
            ->once();

        $this->handler->getHandlers()[ExpectList::class]($assertion);
    }

    public function testAssertListUsesConfiguredAttributePrefix(): void
    {
        $matcher = new Text('First item');
        $assertion = new ExpectList('recent-items', [$matcher]);
        $automation = mock(CypressAutomationInterface::class, [
            'getCy' => $this->cy,
            'getAttributePrefix' => 'my-app',
        ]);
        $handler = new ListRegionAssertionHandler($automation, $this->matcherRegistry);
        $regionChain = mock();
        $region = mock();
        $region->allows()
            ->attr('data-my-app-match-type')
            ->andReturnNull();
        $itemChain = mock();

        $this->cy->expects()
            ->get('[data-my-app-region="recent-items"]')
            ->once()
            ->andReturn($regionChain);
        $regionChain->expects()
            ->then(Mockery::on(function (callable $callback) use ($region): bool {
                $callback($region);
                return true;
            }))
            ->once();
        $this->cy->expects()
            ->get('[data-my-app-region="recent-items"] li')
            ->once()
            ->andReturn($itemChain);
        $itemChain->expects()->eq(0)->once()->andReturn($itemChain);
        $this->matcherRegistry->expects()
            ->handleMatcher(
                'default',
                $matcher,
                Mockery::on(fn (Context $context): bool => $context->getTarget() === $itemChain)
            )
            ->once();

        $handler->getHandlers()[ExpectList::class]($assertion);
    }
}
