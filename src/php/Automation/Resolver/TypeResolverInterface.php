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

namespace Tappet\Cypress\Automation\Resolver;

use Tappet\Runner\Exception\UnresolvableTypeException;

/**
 * Interface TypeResolverInterface.
 *
 * Resolves the type of UI component, either via a data- attribute
 * or by inferring it from its DOM.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
interface TypeResolverInterface
{
    /**
     * Resolves the type of field, e.g. "text" or "select".
     *
     * @param mixed $field
     * @param string $fieldHandle
     * @param string $attributePrefix
     * @throws UnresolvableTypeException
     */
    public function resolveFieldType(mixed $field, string $fieldHandle, string $attributePrefix): string;

    /**
     * Resolves the type of interactable, e.g. "hyperlink" or "button".
     *
     * @param mixed $element
     * @param string $interactionHandle
     * @param string $attributePrefix
     * @return string
     * @throws UnresolvableTypeException
     */
    public function resolveInteractionType(mixed $element, string $interactionHandle, string $attributePrefix): string;

    /**
     * Resolves the type of region, e.g. "list", "table" or "text".
     *
     * @param mixed $element
     * @param string $attributePrefix
     * @return string
     */
    public function resolveRegionType(mixed $element, string $attributePrefix): string;
}
