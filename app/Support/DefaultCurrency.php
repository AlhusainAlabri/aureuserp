<?php

namespace App\Support;

use Throwable;
use Webkul\Security\Settings\CurrencySettings;
use Webkul\Support\Models\Currency;

/**
 * The currency code the application formats money in (ISO code such as "OMR").
 *
 * It follows Settings > Currency, then falls back to APP_CURRENCY, so tables,
 * infolists and the money() helper all agree instead of defaulting to USD.
 */
class DefaultCurrency
{
    protected ?string $resolved = null;

    public static function code(): string
    {
        return app(static::class)->get();
    }

    public function get(): string
    {
        return $this->resolved ??= $this->resolve();
    }

    protected function resolve(): string
    {
        $fallback = strtoupper((string) config('app.currency', 'OMR'));

        try {
            $currencyId = app(CurrencySettings::class)->default_currency_id;

            $code = $currencyId ? Currency::query()->whereKey($currencyId)->value('name') : null;

            return filled($code) ? strtoupper((string) $code) : $fallback;
        } catch (Throwable) {
            return $fallback;
        }
    }
}
