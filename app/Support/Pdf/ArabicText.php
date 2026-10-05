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
        $positions = $arabic->arIdentify($html);

        for ($index = count($positions) - 1; $index >= 0; $index -= 2) {
            $start = $positions[$index - 1];
            $length = $positions[$index] - $start;

            if ($length <= 0) {
                continue;
            }

            $segment = substr($html, $start, $length);
            $shaped = $arabic->utf8Glyphs($segment, max(mb_strlen($segment, 'UTF-8') * 2, 100), false);
            $html = substr_replace($html, $shaped, $start, $length);
        }

        return $html;
    }

    public static function shape(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        if (! self::containsArabic($text)) {
            return $text;
        }

        $arabic = new Arabic;

        return $arabic->utf8Glyphs($text, max(mb_strlen($text, 'UTF-8') * 2, 100), false);
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
