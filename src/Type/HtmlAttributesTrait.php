<?php

namespace Kematjaya\BaseControllerBundle\Type;

/**
 * Builds an escaped HTML attribute string from a form `attr` array.
 *
 * The autocomplete templates render this string with Twig's `raw` filter, so
 * the escaping has to happen here. Without it, a value containing a double
 * quote breaks out of the attribute and injects arbitrary markup.
 *
 * @author Nur Hidayatullah <kematjaya0@gmail.com>
 */
trait HtmlAttributesTrait
{
    /**
     * @param array<string, mixed> $attr
     */
    protected function buildHtmlAttributes(array $attr):string
    {
        $parts = [];
        foreach ($attr as $key => $value) {
            if (null === $value || false === $value) {
                continue;
            }
            
            $parts[] = sprintf(
                '%s="%s"',
                $this->escapeAttribute((string) $key),
                $this->escapeAttribute(is_scalar($value) ? (string) $value : '')
            );
        }
        
        return implode(' ', $parts);
    }
    
    protected function escapeAttribute(string $value):string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
