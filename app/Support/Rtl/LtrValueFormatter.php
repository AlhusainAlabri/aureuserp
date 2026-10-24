<?php

namespace App\Support\Rtl;

use Illuminate\Support\HtmlString;

/**
 * Keeps phone numbers, emails, URLs and codes readable in right-to-left pages.
 *
 * A value such as "+96824123456" has no strong direction, so in an RTL page the
 * plus sign is pushed to the wrong end. Wrapping Latin-only values in an LTR
 * isolate fixes that without changing alignment.
 */
class LtrValueFormatter
{
    protected const NAME_PATTERN = '/(^|_)(phone|mobile|tel|telephone|fax|whatsapp|email|website|url|sku|barcode|iban|swift|number|reference|ref|code|serial)(s)?(_no|_number)?$/';

    public static function appliesTo(string $name): bool
    {
        $field = strtolower(last(explode('.', $name)));

        return (bool) preg_match(self::NAME_PATTERN, $field);
    }

    public static function format(mixed $state): mixed
    {
        if (! is_string($state) && ! is_int($state) && ! is_float($state)) {
            return $state;
        }

        $value = (string) $state;

        if ($value === '' || preg_match('/\p{Arabic}/u', $value) === 1) {
            return $state;
        }

        return new HtmlString('<bdi dir="ltr">'.e($value).'</bdi>');
    }
}
