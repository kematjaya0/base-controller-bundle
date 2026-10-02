<?php

namespace Kematjaya\BaseControllerBundle\Tests\Type;

use Kematjaya\BaseControllerBundle\Type\HtmlAttributesTrait;
use PHPUnit\Framework\TestCase;

final class HtmlAttributesHarness
{
    use HtmlAttributesTrait;

    public function build(array $attr): string
    {
        return $this->buildHtmlAttributes($attr);
    }
}

/**
 * The autocomplete templates render this string through Twig's `raw` filter,
 * so escaping has to happen here or a quote in a value becomes markup.
 */
final class HtmlAttributesTraitTest extends TestCase
{
    private function build(array $attr): string
    {
        return (new HtmlAttributesHarness())->build($attr);
    }

    public function testRendersPlainAttributes(): void
    {
        $this->assertSame('id="ajaxForm" class="form-control"', $this->build([
            'id' => 'ajaxForm',
            'class' => 'form-control',
        ]));
    }

    public function testSkipsNullAndFalseValues(): void
    {
        $out = $this->build([
            'id' => 'x',
            'placeholder' => null,
            'disabled' => false,
        ]);

        $this->assertSame('id="x"', $out);
        $this->assertStringNotContainsString('placeholder', $out);
        $this->assertStringNotContainsString('disabled', $out);
    }

    public function testRendersBooleanTrueAndZero(): void
    {
        // false is skipped, so true/0/'' must still be representable.
        $out = $this->build(['a' => true, 'b' => 0, 'c' => '']);

        $this->assertSame('a="1" b="0" c=""', $out);
    }

    public function testNonScalarValueBecomesEmptyStringRatherThanArrayToString(): void
    {
        $out = $this->build(['data-opt' => ['a', 'b']]);

        $this->assertSame('data-opt=""', $out);
    }

    /**
     * The core invariant: the number of raw double quotes in the output equals
     * the number of attributes. Any extra quote means a value escaped its
     * quotes and can inject new attributes.
     *
     * @dataProvider breakoutProvider
     */
    public function testNoValueCanBreakOutOfItsQuotes(string $payload): void
    {
        $out = $this->build(['id' => $payload]);

        $this->assertSame(
            2,
            substr_count($out, '"'),
            'unescaped double quote leaked into: ' . $out
        );
        $this->assertSame('id="' . htmlspecialchars($payload, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"', $out);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public function breakoutProvider(): array
    {
        return [
            'double quote then handler' => ['" onmouseover="alert(1)'],
            'single quote' => ["' onfocus='alert(1)"],
            'angle brackets' => ['<script>alert(1)</script>'],
            'ampersand' => ['a&b'],
            'already an entity' => ['&quot;'],
            'tag close then attr' => ['"><svg onload=alert(1)>'],
            'backslash quote' => ['\\" onload=alert(1)'],
        ];
    }

    public function testAttributeNamesAreEscapedToo(): void
    {
        $out = $this->build(['a"b' => 'c']);

        // The key must not be able to introduce a second attribute either.
        $this->assertSame(2, substr_count($out, '"'));
        $this->assertStringNotContainsString('a"b', $out);
    }

    public function testEmptyArrayProducesEmptyString(): void
    {
        $this->assertSame('', $this->build([]));
    }
}
