<?php

declare(strict_types=1);

namespace App;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

class ArticleContent
{
    private const TAGS = ['p', 'div', 'br', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'sub', 'sup', 'mark', 'span', 'font', 'ul', 'ol', 'li', 'blockquote', 'a', 'hr', 'pre', 'code', 'table', 'caption', 'thead', 'tbody', 'tfoot', 'tr', 'td', 'th', 'figure', 'figcaption', 'img', 'details', 'summary'];

    private const DROP = ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'video', 'audio', 'form', 'input', 'textarea', 'button', 'noscript', 'template', 'link', 'meta'];

    public static function imageUrl(string $url): ?string
    {
        $url = trim($url);
        if (preg_match('~[\s\x00-\x1F\x7F\\\\]~', $url)) {
            return null;
        }

        if (preg_match('~^/(?!/)[a-z0-9/_-]+\.(?:jpe?g|png|webp)$~i', $url)) {
            return $url;
        }

        return filter_var($url, FILTER_VALIDATE_URL) && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true) ? $url : null;
    }

    public static function sanitize(?string $html): string
    {
        if (trim((string) $html) === '') {
            return '';
        }

        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML('<?xml encoding="UTF-8"><body>'.$html.'</body>', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
            $body = $document->getElementsByTagName('body')->item(0);

            return $body ? self::children($body) : '';
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    public static function text(?string $html): string
    {
        $spaced = preg_replace('/<\/(?:p|div|h[1-6]|li|td|th|tr|blockquote|figure|figcaption|pre|details|summary|caption)>|<br\s*\/?\s*>/i', ' ', (string) $html) ?? '';

        return trim(preg_replace('/[\s\x{00A0}\x{200B}]+/u', ' ', html_entity_decode(strip_tags($spaced), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
    }

    private static function children(DOMNode $parent, int $depth = 0): string
    {
        if ($depth > 60) {
            return '';
        }

        $html = '';
        foreach ($parent->childNodes as $node) {
            if ($node instanceof DOMText) {
                $html .= htmlspecialchars($node->textContent, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            } elseif ($node instanceof DOMElement) {
                $tag = strtolower($node->tagName);
                if (in_array($tag, self::DROP, true)) {
                    continue;
                }
                if ($tag === 'img') {
                    $src = self::imageUrl($node->getAttribute('src'));
                    if ($src !== null) {
                        $html .= '<img src="'.htmlspecialchars($src, ENT_QUOTES, 'UTF-8').'" alt="'.htmlspecialchars($node->getAttribute('alt'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'" loading="lazy">';
                    }

                    continue;
                }
                $content = self::children($node, $depth + 1);
                if (! in_array($tag, self::TAGS, true)) {
                    $html .= $content;

                    continue;
                }

                $attributes = '';
                if ($tag === 'div' && in_array($node->getAttribute('data-callout'), ['info', 'tip', 'warning'], true)) {
                    $attributes .= ' data-callout="'.$node->getAttribute('data-callout').'"';
                }
                if ($tag === 'figure') {
                    foreach (['data-align' => ['left', 'center', 'right'], 'data-width' => ['25', '50', '75', '100']] as $attribute => $values) {
                        if (in_array($node->getAttribute($attribute), $values, true)) {
                            $attributes .= ' '.$attribute.'="'.$node->getAttribute($attribute).'"';
                        }
                    }
                }
                if ($tag === 'ol') {
                    if (in_array($node->getAttribute('type'), ['1', 'a', 'A', 'i', 'I'], true)) {
                        $attributes .= ' type="'.$node->getAttribute('type').'" data-list-type="'.$node->getAttribute('type').'"';
                    }
                    if (in_array($node->getAttribute('data-list-suffix'), ['period', 'paren'], true)) {
                        $attributes .= ' data-list-suffix="'.$node->getAttribute('data-list-suffix').'"';
                    }
                    $start = $node->getAttribute('start');
                    if (preg_match('/^\d{1,4}$/', $start) && (int) $start > 0) {
                        $attributes .= ' start="'.$start.'"';
                    }
                }
                if ($tag === 'li' && preg_match('/^[1-9]\d{0,3}$/', $node->getAttribute('value'))) {
                    $attributes .= ' value="'.$node->getAttribute('value').'"';
                }
                $styles = self::styles($node->getAttribute('style'));
                if ($tag === 'font') {
                    $tag = 'span';
                    $color = self::color($node->getAttribute('color'));
                    if ($color !== null) {
                        $styles .= 'color:'.$color.';';
                    }
                    $sizes = ['1' => 12, '2' => 14, '3' => 16, '4' => 18, '5' => 24, '6' => 32, '7' => 40];
                    if (isset($sizes[$node->getAttribute('size')])) {
                        $styles .= 'font-size:'.$sizes[$node->getAttribute('size')].'px;';
                    }
                }
                if ($styles !== '') {
                    $attributes .= ' style="'.htmlspecialchars($styles, ENT_QUOTES, 'UTF-8').'"';
                }
                if ($tag === 'a') {
                    $href = trim($node->getAttribute('href'));
                    if (preg_match('~^(?:https?://|mailto:)[^\s\x00-\x1F\x7F]+$~i', $href)) {
                        $attributes .= ' href="'.htmlspecialchars($href, ENT_QUOTES, 'UTF-8').'" rel="noopener noreferrer"';
                        if ($node->getAttribute('target') === '_blank') {
                            $attributes .= ' target="_blank"';
                        }
                    }
                }
                if (in_array($tag, ['td', 'th'], true)) {
                    foreach (['colspan', 'rowspan'] as $attribute) {
                        $value = $node->getAttribute($attribute);
                        if (ctype_digit($value) && (int) $value >= 1 && (int) $value <= 10) {
                            $attributes .= ' '.$attribute.'="'.$value.'"';
                        }
                    }
                }
                $html .= '<'.$tag.$attributes.'>'.(in_array($tag, ['br', 'hr'], true) ? '' : $content.'</'.$tag.'>');
            }
        }

        return $html;
    }

    private static function styles(string $style): string
    {
        $safe = '';
        foreach (explode(';', $style) as $declaration) {
            $parts = explode(':', $declaration, 2);
            if (count($parts) !== 2) {
                continue;
            }
            [$property, $value] = array_map('trim', $parts);
            $property = strtolower($property);
            $value = strtolower($value);
            $allowed = match ($property) {
                'text-align' => in_array($value, ['left', 'center', 'right', 'justify'], true),
                'font-weight' => in_array($value, ['bold', 'normal', '400', '700'], true),
                'font-style' => in_array($value, ['italic', 'normal'], true),
                'text-decoration', 'text-decoration-line' => in_array($value, ['underline', 'line-through', 'underline line-through', 'none'], true),
                'font-size' => preg_match('/^(12|14|16|18|24|32|40)px$/', $value) === 1,
                'color', 'background-color' => self::color($value) !== null,
                default => false,
            };
            if ($allowed) {
                $safe .= $property.':'.$value.';';
            }
        }

        return $safe;
    }

    private static function color(string $value): ?string
    {
        return preg_match('/^(?:#[0-9a-f]{3}(?:[0-9a-f]{3})?|rgb\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*\)|black|white|red|blue|green|transparent)$/i', $value) ? strtolower($value) : null;
    }
}
