<?php

namespace Kematjaya\BaseControllerBundle\Tests\Functional;

/**
 * AutoCompleteType behaviour against the real FormFactory: the `attr` normalizer
 * must merge caller attributes with the autocomplete defaults (README 4.6), and
 * the generated attribute string must be HTML-escaped (README 4.7).
 */
final class AutoCompleteTypeTest extends FunctionalTestCase
{
    public function testCallerAttributesArePreservedAlongsideTheDefaults(): void
    {
        $attributes = $this->renderFieldAttributes();

        // README 4.6: the old normalizer replaced the array and silently
        // dropped every caller attribute.
        self::assertStringContainsString('id="cityField"', $attributes);
        self::assertStringContainsString('data-test="kept"', $attributes);

        // ...while still applying the autocomplete defaults.
        self::assertStringContainsString('class="autocomplete form-control"', $attributes);
        self::assertStringContainsString('url="/autocomplete/city"', $attributes);
    }

    public function testACallerSuppliedClassWinsOverTheDefault(): void
    {
        $this->client->request('GET', '/probe/form?class=my-own-class');

        self::assertResponseIsSuccessful();

        $attributes = $this->requestPayload()['html_attributes'];

        self::assertStringContainsString('class="my-own-class"', $attributes);
        self::assertStringNotContainsString('autocomplete form-control', $attributes);
    }

    public function testTheDomParentIsExposedAsAppendTo(): void
    {
        $this->client->request('GET', '/probe/form');

        self::assertResponseIsSuccessful();

        self::assertSame('#city-list', $this->requestPayload()['appendTo']);
    }

    /**
     * README 4.7: a value containing a double quote must not be able to close
     * the attribute and inject an event handler.
     */
    public function testAQuoteInAnAttributeValueCannotBreakOutOfTheAttribute(): void
    {
        $this->client->request('GET', '/probe/form?probe='.urlencode('" onmouseover="alert(1)'));

        self::assertResponseIsSuccessful();
        $attributes = $this->requestPayload()['html_attributes'];

        self::assertStringNotContainsString('onmouseover="', $attributes);
        self::assertStringContainsString('&quot;', $attributes);
    }

    public function testMarkupInAnAttributeValueIsEscaped(): void
    {
        $this->client->request('GET', '/probe/form?probe='.urlencode('<script>alert(1)</script>'));

        self::assertResponseIsSuccessful();
        $attributes = $this->requestPayload()['html_attributes'];

        self::assertStringNotContainsString('<script>', $attributes);
        self::assertStringContainsString('&lt;script&gt;', $attributes);
    }

    public function testAttributeNamesAreEscapedToo(): void
    {
        $this->client->request('GET', '/probe/form?probe='.urlencode('plain-value'));

        self::assertResponseIsSuccessful();
        $attributes = $this->requestPayload()['html_attributes'];

        // Every attribute must be emitted as a quoted key="value" pair.
        self::assertMatchesRegularExpression('/^[\w:-]+="[^"]*"( [\w:-]+="[^"]*")*$/', $attributes);
    }

    private function renderFieldAttributes(): string
    {
        $this->client->request('GET', '/probe/form');

        self::assertResponseIsSuccessful();

        return $this->requestPayload()['html_attributes'];
    }
}
