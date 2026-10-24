<?php

namespace App\Support\Pdf;

use ArPHP\I18N\Arabic;

class ArabicText
{
    /**
     * Dompdf cannot shape Arabic (joining + bidi). Convert Arabic runs in HTML
     * to presentation-form glyphs that a Unicode Arabic font can render.
     */
    public static function shapeHtml(string $html): string
    {
        if ($html === '' || ! self::containsArabic($html)) {
            return $html;
        }

        $arabic = new Arabic;
        $parts = preg_split('/(<!--.*?-->|<[^>]+>)/s', $html, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$html];
        $skipUntil = null;

        foreach ($parts as $index => $part) {
            if ($part === '') {
                continue;
            }

            if ($part[0] === '<') {
                if ($skipUntil === null && preg_match('/^<(style|script)\b/i', $part, $open)) {
                    $skipUntil = strtolower($open[1]);
                } elseif ($skipUntil !== null && preg_match('/^<\/'.$skipUntil.'\b/i', $part)) {
                    $skipUntil = null;
                }

                continue;
            }

            if ($skipUntil !== null || ! self::containsArabic($part)) {
                continue;
            }

            $parts[$index] = htmlspecialchars(
                self::glyphs($arabic, html_entity_decode($part, ENT_QUOTES | ENT_HTML5, 'UTF-8')),
                ENT_NOQUOTES,
                'UTF-8',
            );
        }

        return implode('', $parts);
    }

    public static function shape(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        if (! self::containsArabic($text)) {
            return $text;
        }

        return self::glyphs(new Arabic, $text);
    }

    /**
     * Shape and reorder one run of text for an engine that only draws left to right.
     *
     * The shaper reverses everything it is given, which scrambles digits, dates and codes that sit next
     * to Arabic ("2.350" printed as "350.2"). So only the Arabic words go through it; numbers and Latin
     * text stay in reading order, and the words are laid out right to left.
     */
    protected static function glyphs(Arabic $arabic, string $text): string
    {
        $parts = preg_split('/(\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [$text];

        $tokens = [];
        $latin = [];

        $flushLatin = function () use (&$tokens, &$latin): void {
            if ($latin !== []) {
                $run = implode('', $latin);
                $tokens[] = rtrim($run);

                if (($trailing = substr($run, strlen(rtrim($run)))) !== '') {
                    $tokens[] = $trailing;
                }

                $latin = [];
            }
        };

        foreach ($parts as $part) {
            if (trim($part) === '') {
                if ($latin !== []) {
                    $latin[] = $part;
                } else {
                    $tokens[] = $part;
                }

                continue;
            }

            if (preg_match('/\p{Arabic}/u', $part)) {
                $flushLatin();
                $tokens[] = $arabic->utf8Glyphs($part, max(mb_strlen($part, 'UTF-8') * 2, 100), false);

                continue;
            }

            if (preg_match('/[A-Za-z0-9]/', $part)) {
                $latin[] = $part;

                continue;
            }

            $flushLatin();
            $tokens[] = strtr($part, ['(' => ')', ')' => '(', '[' => ']', ']' => '[', '<' => '>', '>' => '<']);
        }

        $flushLatin();

        return implode('', array_reverse($tokens));
    }

    public static function containsArabic(string $text): bool
    {
        return (bool) preg_match('/\p{Arabic}/u', $text);
    }

    /**
     * Active Arabic PDF font (Amiri by default — see config/pdf.php).
     */
    public static function pdfFontFamily(): string
    {
        return PdfArabicFont::family();
    }
}
