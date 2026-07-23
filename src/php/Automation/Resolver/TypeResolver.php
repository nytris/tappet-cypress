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
 * Class TypeResolver.
 *
 * Resolves the type of UI component, either via a `data-` attribute
 * or by inferring it from its DOM.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class TypeResolver implements TypeResolverInterface
{
    /**
     * @inheritDoc
     */
    public function resolveFieldType(mixed $field, string $fieldHandle, string $attributePrefix): string
    {
        $fieldType = $field->attr('data-' . $attributePrefix . '-field-type');

        if (!$fieldType) {
            switch ($field->prop('tagName')) {
                case 'INPUT':
                    $fieldType = strtolower($field->attr('type'));

                    if ($fieldType === 'password') {
                        $fieldType = 'text';
                    }

                    break;
                case 'SELECT':
                    $fieldType = 'select';
                    break;
                case 'TEXTAREA':
                    $fieldType = 'text';
                    break;
                default:
                    throw new UnresolvableTypeException(
                        'No field type could be resolved for field with handle "' . $fieldHandle . '"'
                    );
            }
        }

        return $fieldType;
    }

    /**
     * @inheritDoc
     */
    public function resolveInteractionType(mixed $element, string $interactionHandle, string $attributePrefix): string
    {
        $interactionType = $element->attr('data-' . $attributePrefix . '-interaction-type');

        if (!$interactionType) {
            switch ($element->prop('tagName')) {
                case 'A':
                    if ($element->attr('href') !== null) {
                        $interactionType = 'hyperlink';
                    } else {
                        throw new UnresolvableTypeException(
                            'No interaction type could be resolved for interaction with handle "' . $interactionHandle . '"'
                        );
                    }
                    break;
                case 'BUTTON':
                    $interactionType = 'button';
                    break;
                case 'INPUT':
                    if (strtolower($element->attr('type')) === 'button') {
                        $interactionType = 'button';
                    } else {
                        throw new UnresolvableTypeException(
                            'No interaction type could be resolved for interaction with handle "' . $interactionHandle . '"'
                        );
                    }
                    break;
                default:
                    throw new UnresolvableTypeException(
                        'No interaction type could be resolved for interaction with handle "' . $interactionHandle . '"'
                    );
            }
        }

        return $interactionType;
    }

    /**
     * @inheritDoc
     */
    public function resolveRegionType(mixed $element, string $attributePrefix): string
    {
        $regionType = $element->attr('data-' . $attributePrefix . '-region-type');

        if (!$regionType) {
            switch ($element->prop('tagName')) {
                case 'OL':
                case 'UL':
                    $regionType = 'list';
                    break;
                case 'TABLE':
                    $regionType = 'table';
                    break;
                default:
                    $regionType = 'text';
            }
        }

        return $regionType;
    }
}
