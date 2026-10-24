<?php

namespace App\Support\Pdf\Documents;

use Webkul\Support\Models\Currency;

class DocumentMoney
{
    public function __construct(protected ?Currency $currency) {}

    public function decimals(): int
    {
        return (int) ($this->currency?->decimal_places ?? 3);
    }

    public function number(float|int|string|null $amount): string
    {
        return number_format((float) $amount, $this->decimals());
    }

    public function format(float|int|string|null $amount): string
    {
        return $this->symbol().' '.$this->number($amount);
    }

    public function symbol(): string
    {
        if ($this->currency?->symbol) {
            return $this->currency->symbol;
        }

        return app()->getLocale() === 'ar' ? 'ر.ع.' : 'OMR';
    }
}
