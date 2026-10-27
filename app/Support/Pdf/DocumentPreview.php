<?php

namespace App\Support\Pdf;

use Illuminate\Contracts\View\View;
use Illuminate\Support\HtmlString;

/**
 * Turns a rendered print document (a full HTML page built for the PDF engine) into
 * a block that is safe to show inside a modal: its CSS only applies to the document
 * and it sits on a white sheet, so it stays readable in the dark theme.
 */
class DocumentPreview
{
    public const CONTAINER_CLASS = 'trade-document-preview';

    public static function make(View|string $document): HtmlString
    {
        $html = $document instanceof View ? $document->render() : $document;

        $styles = '';

        if (preg_match_all('/<style\b[^>]*>(.*?)<\/style>/is', $html, $matches)) {
            $styles = implode("\n", array_map(static::scopeStyles(...), $matches[1]));
        }

        $body = preg_match('/<body\b[^>]*>(.*)<\/body>/is', $html, $match) ? $match[1] : $html;

        $class = static::CONTAINER_CLASS;

        return new HtmlString(
            "<style>.{$class}{background:#ffffff;color:#1f2937;border-radius:.5rem;padding:1.25rem;overflow-x:auto;}\n{$styles}</style>"
            ."<div class=\"{$class}\">{$body}</div>"
        );
    }

    protected static function scopeStyles(string $css): string
    {
        $class = '.'.static::CONTAINER_CLASS;

        $css = preg_replace('/@page\s*\{[^}]*\}/i', '', $css);

        return (string) preg_replace_callback(
            '/(^|\})\s*([^{}@]+)\{/',
            static function (array $match) use ($class): string {
                $selectors = array_map(
                    static fn (string $selector): string => preg_match('/^body$/i', trim($selector))
                        ? $class
                        : $class.' '.trim($selector),
                    explode(',', $match[2]),
                );

                return $match[1].implode(',', $selectors).'{';
            },
            $css,
        );
    }
}
