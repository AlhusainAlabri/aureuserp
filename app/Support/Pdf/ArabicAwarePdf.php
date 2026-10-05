<?php

namespace App\Support\Pdf;

use Barryvdh\DomPDF\PDF;

class ArabicAwarePdf extends PDF
{
    protected bool $requiresArabicFont = false;

    protected string $resolvedArabicFont = 'DejaVu Sans';

    /**
     * Shape Arabic text before Dompdf renders so letters connect and RTL order is correct.
     *
     * @param  string|null  $encoding  Not used yet
     */
    public function loadHTML(string $string, ?string $encoding = null): self
    {
        if (ArabicText::containsArabic($string)) {
            $this->requiresArabicFont = true;
            $this->resolvedArabicFont = $this->resolveArabicFontFamily();
            $string = ArabicText::shapeHtml($string);
            $string = $this->injectArabicFontStyles($string, $this->resolvedArabicFont);
            $this->getDomPDF()->getOptions()->setDefaultFont($this->resolvedArabicFont);
        }

        return parent::loadHTML($string, $encoding);
    }

    protected function injectArabicFontStyles(string $html, string $font): string
    {
        $face = PdfArabicFont::fontFaceCss($font);
        $style = '<style id="arabic-pdf-font">'.$face.' body, table, th, td, div, p, span, h1, h2, h3, h4, li { font-family: "'.$font.'", "DejaVu Sans", sans-serif !important; }</style>';

        if (stripos($html, '</head>') !== false) {
            return preg_replace('/<\/head>/i', $style.'</head>', $html, 1) ?? ($style.$html);
        }

        return $style.$html;
    }

    /**
     * @param  array<string, mixed>|string  $attribute
     * @param  null|mixed  $value
     */
    public function setOption($attribute, $value = null): self
    {
        if ($this->requiresArabicFont) {
            if (is_array($attribute) && array_key_exists('defaultFont', $attribute)) {
                $attribute['defaultFont'] = $this->resolvedArabicFont;
            }

            if ($attribute === 'defaultFont') {
                $value = $this->resolvedArabicFont;
            }
        }

        return parent::setOption($attribute, $value);
    }

    public function render(): void
    {
        if ($this->requiresArabicFont) {
            $this->getDomPDF()->getOptions()->setDefaultFont($this->resolvedArabicFont);
        }

        parent::render();
    }

    protected function resolveArabicFontFamily(): string
    {
        $family = ArabicText::pdfFontFamily();

        if ($family === 'DejaVu Sans') {
            return $family;
        }

        if (! PdfArabicFont::register($this->getDomPDF(), $family)) {
            return 'DejaVu Sans';
        }

        return $family;
    }
}
