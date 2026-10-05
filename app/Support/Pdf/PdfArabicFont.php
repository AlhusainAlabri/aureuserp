<?php

namespace App\Support\Pdf;

use Dompdf\Dompdf;
use Throwable;

class PdfArabicFont
{
    /**
     * @var array<string, bool>
     */
    protected static array $registered = [];

    public static function family(): string
    {
        $configured = (string) config('pdf.arabic_font', 'Amiri');

        if ($configured === 'DejaVu Sans') {
            return 'DejaVu Sans';
        }

        if (! self::isBundledAndPresent($configured)) {
            return 'DejaVu Sans';
        }

        // Dompdf must generate .ufm metrics into storage/fonts on first use.
        if (! self::fontDirIsWritable()) {
            return 'DejaVu Sans';
        }

        return $configured;
    }

    /**
     * @return array{normal: ?string, bold: ?string}
     */
    public static function paths(?string $family = null): array
    {
        $family ??= self::family();
        $paths = config("pdf.fonts.{$family}", []);

        return [
            'normal' => $paths['normal'] ?? null,
            'bold'   => $paths['bold'] ?? null,
        ];
    }

    public static function fontFaceCss(?string $family = null): string
    {
        $family ??= self::family();
        $paths = self::paths($family);

        if (blank($paths['normal']) || ! is_file($paths['normal'])) {
            return '';
        }

        $css = sprintf(
            "@font-face { font-family: '%s'; font-style: normal; font-weight: normal; src: url('%s') format('truetype'); }",
            $family,
            self::fileUrl($paths['normal']),
        );

        if (filled($paths['bold']) && is_file($paths['bold'])) {
            $css .= sprintf(
                " @font-face { font-family: '%s'; font-style: normal; font-weight: bold; src: url('%s') format('truetype'); }",
                $family,
                self::fileUrl($paths['bold']),
            );
        }

        return $css;
    }

    public static function register(Dompdf $dompdf, ?string $family = null): bool
    {
        $family ??= self::family();

        if ($family === 'DejaVu Sans') {
            return true;
        }

        $paths = self::paths($family);

        if (blank($paths['normal']) || ! is_file($paths['normal'])) {
            return false;
        }

        if (! self::fontDirIsWritable($dompdf->getOptions()->getFontDir())) {
            return false;
        }

        $cacheKey = $family.'|'.md5(($paths['normal'] ?? '').'|'.($paths['bold'] ?? ''));

        if (isset(self::$registered[$cacheKey])) {
            return true;
        }

        try {
            $metrics = $dompdf->getFontMetrics();

            $registered = $metrics->registerFont(
                ['family' => $family, 'style' => 'normal', 'weight' => 'normal'],
                self::fileUrl($paths['normal']),
            );

            if ($registered === false) {
                return false;
            }

            if (filled($paths['bold']) && is_file($paths['bold'])) {
                $boldRegistered = $metrics->registerFont(
                    ['family' => $family, 'style' => 'normal', 'weight' => 'bold'],
                    self::fileUrl($paths['bold']),
                );

                if ($boldRegistered === false) {
                    return false;
                }
            }
        } catch (Throwable) {
            return false;
        }

        self::$registered[$cacheKey] = true;

        return true;
    }

    public static function fontDirIsWritable(?string $fontDir = null): bool
    {
        $fontDir ??= storage_path('fonts');

        if (! is_dir($fontDir) && ! @mkdir($fontDir, 0775, true) && ! is_dir($fontDir)) {
            return false;
        }

        return is_writable($fontDir);
    }

    protected static function isBundledAndPresent(string $family): bool
    {
        if ($family === 'DejaVu Sans') {
            return true;
        }

        $paths = config("pdf.fonts.{$family}");

        return is_array($paths)
            && filled($paths['normal'] ?? null)
            && is_file($paths['normal']);
    }

    protected static function fileUrl(string $path): string
    {
        return 'file://'.str_replace('\\', '/', $path);
    }
}
