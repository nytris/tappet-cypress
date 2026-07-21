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

namespace Tappet\Cypress\Tests\Functional\Fixtures\MyTestApp\test\app\Transition;

use Tappet\Runner\Transition\TransitionInterface;

/**
 * Class ModalOpenTransition.
 *
 * Represents a modal becoming visible.
 * Detected by a MutationObserver registered via the suite's TestPlugin.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class ModalOpenTransition implements TransitionInterface
{
    public function __construct(
        private readonly string $handle
    ) {
    }

    /**
     * @inheritDoc
     */
    public function equals(TransitionInterface $other): bool
    {
        return $other instanceof ModalOpenTransition && $other->handle === $this->handle;
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'modal "' . $this->handle . '" opening';
    }
}
