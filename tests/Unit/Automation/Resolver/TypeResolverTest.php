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

namespace Tappet\Cypress\Tests\Unit\Automation\Resolver;

use Tappet\Cypress\Automation\Resolver\TypeResolver;
use Tappet\Cypress\Tests\AbstractTestCase;
use Tappet\Runner\Exception\UnresolvableTypeException;

/**
 * Class TypeResolverTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class TypeResolverTest extends AbstractTestCase
{
    private TypeResolver $resolver;

    public function setUp(): void
    {
        parent::setUp();

        $this->resolver = new TypeResolver();
    }

    public function testResolveFieldTypeReturnsExplicitFieldTypeAttribute(): void
    {
        $field = mock();
        $field->allows('attr')->with('data-ui-field-type')->andReturn('text');

        static::assertSame('text', $this->resolver->resolveFieldType($field, 'username', 'ui'));
    }

    public function testResolveFieldTypeResolvesTextForInputFieldType(): void
    {
        $field = mock();
        $field->allows('attr')->with('data-ui-field-type')->andReturn(null);
        $field->allows('prop')->with('tagName')->andReturn('INPUT');
        $field->allows('attr')->with('type')->andReturn('Text');

        static::assertSame('text', $this->resolver->resolveFieldType($field, 'username', 'ui'));
    }

    public function testResolveFieldTypeResolvesTextForPasswordInputType(): void
    {
        $field = mock();
        $field->allows('attr')->with('data-ui-field-type')->andReturn(null);
        $field->allows('prop')->with('tagName')->andReturn('INPUT');
        $field->allows('attr')->with('type')->andReturn('password');

        static::assertSame('text', $this->resolver->resolveFieldType($field, 'password', 'ui'));
    }

    public function testResolveFieldTypeResolvesSelectForSelectElement(): void
    {
        $field = mock();
        $field->allows('attr')->with('data-ui-field-type')->andReturn(null);
        $field->allows('prop')->with('tagName')->andReturn('SELECT');

        static::assertSame('select', $this->resolver->resolveFieldType($field, 'country', 'ui'));
    }

    public function testResolveFieldTypeResolvesTextForTextareaElement(): void
    {
        $field = mock();
        $field->allows('attr')->with('data-ui-field-type')->andReturn(null);
        $field->allows('prop')->with('tagName')->andReturn('TEXTAREA');

        static::assertSame('text', $this->resolver->resolveFieldType($field, 'bio', 'ui'));
    }

    public function testResolveFieldTypeThrowsForUnrecognisedElement(): void
    {
        $field = mock();
        $field->allows('attr')->with('data-ui-field-type')->andReturn(null);
        $field->allows('prop')->with('tagName')->andReturn('DIV');

        $this->expectException(UnresolvableTypeException::class);
        $this->expectExceptionMessage('No field type could be resolved for field with handle "username"');

        $this->resolver->resolveFieldType($field, 'username', 'ui');
    }

    public function testResolveFieldTypeUsesConfiguredAttributePrefix(): void
    {
        $field = mock();
        $field->allows('attr')->with('data-my-app-field-type')->andReturn('text');

        static::assertSame('text', $this->resolver->resolveFieldType($field, 'username', 'my-app'));
    }

    public function testResolveInteractionTypeReturnsExplicitInteractionTypeAttribute(): void
    {
        $element = mock();
        $element->allows('attr')->with('data-ui-interaction-type')->andReturn('click');

        static::assertSame('click', $this->resolver->resolveInteractionType($element, 'submit-button', 'ui'));
    }

    public function testResolveInteractionTypeResolvesButtonForButtonElement(): void
    {
        $element = mock();
        $element->allows('attr')->with('data-ui-interaction-type')->andReturn(null);
        $element->allows('prop')->with('tagName')->andReturn('BUTTON');

        static::assertSame('button', $this->resolver->resolveInteractionType($element, 'submit-button', 'ui'));
    }

    public function testResolveInteractionTypeResolvesHyperlinkForAnchorElementWithHrefAttribute(): void
    {
        $element = mock();
        $element->allows('attr')->with('data-ui-interaction-type')->andReturn(null);
        $element->allows('prop')->with('tagName')->andReturn('A');
        $element->allows('attr')->with('href')->andReturn('/my/url');

        static::assertSame('hyperlink', $this->resolver->resolveInteractionType($element, 'my-link', 'ui'));
    }

    public function testResolveInteractionTypeThrowsForAnchorElementWithNoHrefAttribute(): void
    {
        $element = mock();
        $element->allows('attr')->with('data-ui-interaction-type')->andReturn(null);
        $element->allows('prop')->with('tagName')->andReturn('A');
        $element->allows('attr')->with('href')->andReturn(null);

        $this->expectException(UnresolvableTypeException::class);
        $this->expectExceptionMessage('No interaction type could be resolved for interaction with handle "my-link"');

        $this->resolver->resolveInteractionType($element, 'my-link', 'ui');
    }

    public function testResolveInteractionTypeResolvesButtonForInputButtonElement(): void
    {
        $element = mock();
        $element->allows('attr')->with('data-ui-interaction-type')->andReturn(null);
        $element->allows('prop')->with('tagName')->andReturn('INPUT');
        $element->allows('attr')->with('type')->andReturn('button');

        static::assertSame('button', $this->resolver->resolveInteractionType($element, 'submit-input', 'ui'));
    }

    public function testResolveInteractionTypeThrowsForNonButtonInputElement(): void
    {
        $element = mock();
        $element->allows('attr')->with('data-ui-interaction-type')->andReturn(null);
        $element->allows('prop')->with('tagName')->andReturn('INPUT');
        $element->allows('attr')->with('type')->andReturn('text');

        $this->expectException(UnresolvableTypeException::class);
        $this->expectExceptionMessage('No interaction type could be resolved for interaction with handle "my-input"');

        $this->resolver->resolveInteractionType($element, 'my-input', 'ui');
    }

    public function testResolveInteractionTypeThrowsForUnrecognisedElement(): void
    {
        $element = mock();
        $element->allows('attr')->with('data-ui-interaction-type')->andReturn(null);
        $element->allows('prop')->with('tagName')->andReturn('DIV');

        $this->expectException(UnresolvableTypeException::class);
        $this->expectExceptionMessage('No interaction type could be resolved for interaction with handle "my-div"');

        $this->resolver->resolveInteractionType($element, 'my-div', 'ui');
    }

    public function testResolveInteractionTypeUsesConfiguredAttributePrefix(): void
    {
        $element = mock();
        $element->allows('attr')->with('data-my-app-interaction-type')->andReturn('click');

        static::assertSame('click', $this->resolver->resolveInteractionType($element, 'submit-button', 'my-app'));
    }

    public function testResolveRegionTypeReturnsExplicitRegionTypeAttribute(): void
    {
        $element = mock();
        $element->allows('attr')->with('data-ui-region-type')->andReturn('text');

        static::assertSame('text', $this->resolver->resolveRegionType($element, 'ui'));
    }

    public function testResolveRegionTypeDefaultsToTextForUnrecognisedElement(): void
    {
        $element = mock();
        $element->allows('attr')->with('data-ui-region-type')->andReturn(null);
        $element->allows('prop')->with('tagName')->andReturn('DIV');

        static::assertSame('text', $this->resolver->resolveRegionType($element, 'ui'));
    }

    public function testResolveRegionTypeResolvesTableForTableElement(): void
    {
        $element = mock();
        $element->allows('attr')->with('data-ui-region-type')->andReturn(null);
        $element->allows('prop')->with('tagName')->andReturn('TABLE');

        static::assertSame('table', $this->resolver->resolveRegionType($element, 'ui'));
    }

    public function testResolveRegionTypeResolvesListForUlElement(): void
    {
        $element = mock();
        $element->allows('attr')->with('data-ui-region-type')->andReturn(null);
        $element->allows('prop')->with('tagName')->andReturn('UL');

        static::assertSame('list', $this->resolver->resolveRegionType($element, 'ui'));
    }

    public function testResolveRegionTypeResolvesListForOlElement(): void
    {
        $element = mock();
        $element->allows('attr')->with('data-ui-region-type')->andReturn(null);
        $element->allows('prop')->with('tagName')->andReturn('OL');

        static::assertSame('list', $this->resolver->resolveRegionType($element, 'ui'));
    }

    public function testResolveRegionTypeUsesConfiguredAttributePrefix(): void
    {
        $element = mock();
        $element->allows('attr')->with('data-my-app-region-type')->andReturn('my-custom-type');

        static::assertSame('my-custom-type', $this->resolver->resolveRegionType($element, 'my-app'));
    }
}
