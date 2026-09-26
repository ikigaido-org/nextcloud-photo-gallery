<?php
declare(strict_types=1);
// SPDX-License-Identifier: AGPL-3.0-only
namespace OCA\PhotoGallery\Service;

/** Rebuild a small formatting subset; never echo arbitrary administrator HTML. */
final class Footer {
    public static function clean(string $html): string {
        if (strlen($html) > 16000) { throw new \InvalidArgumentException('Footer: höchstens 16000 Bytes.'); }
        if (trim($html) === '') { return ''; }
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        try {
            $doc->loadHTML('<?xml encoding="UTF-8"><!doctype html><html><body>' . $html . '</body></html>', LIBXML_NONET);
            $body = $doc->getElementsByTagName('body')->item(0);
            return $body ? self::children($body) : '';
        } finally { libxml_clear_errors(); libxml_use_internal_errors($previous); }
    }
    private static function escape(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
    private static function children(\DOMNode $parent): string {
        $out = '';
        foreach ($parent->childNodes as $node) {
            if ($node instanceof \DOMText) { $out .= self::escape($node->textContent); continue; }
            if (!$node instanceof \DOMElement) { continue; }
            $tag = strtolower($node->tagName);
            if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'form', 'input', 'textarea', 'template', 'link', 'meta', 'img'], true)) { continue; }
            $inner = self::children($node);
            if (!in_array($tag, ['p', 'br', 'strong', 'b', 'em', 'i', 'span', 'ul', 'ol', 'li', 'a'], true)) { $out .= $inner; continue; }
            if ($tag === 'br') { $out .= '<br>'; continue; }
            $attributes = '';
            if ($tag === 'a') {
                $href = trim($node->getAttribute('href'));
                $valid = !preg_match('/[\x00-\x20\x7f\\\\<>]/', $href)
                    && ((preg_match('~^https?://~i', $href) && filter_var($href, FILTER_VALIDATE_URL))
                        || (str_starts_with($href, 'mailto:') && filter_var(substr($href, 7), FILTER_VALIDATE_EMAIL))
                        || (str_starts_with($href, '/') && !str_starts_with($href, '//'))
                        || str_starts_with($href, '#'));
                if ($valid) { $attributes = ' href="' . self::escape($href) . '" rel="noopener noreferrer"'; }
            }
            $out .= '<' . $tag . $attributes . '>' . $inner . '</' . $tag . '>';
        }
        return $out;
    }
}
