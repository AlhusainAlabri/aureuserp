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

    /**
     * Id of the default currency, or null if that currency does not exist.
     */
    public static function id(): ?int
    {
        $id = Currency::query()->where('name', static::code())->value('id');

        return $id === null ? null : (int) $id;
    }

    /**
     * Decimal places of the default currency (3 for OMR, 2 for USD).
     */
    public static function decimals(): int
    {
        return app(static::class)->resolveDecimals();
    }

    protected ?int $resolvedDecimals = null;

    public function resolveDecimals(): int
    {
        if ($this->resolvedDecimals !== null) {
            return $this->resolvedDecimals;
        }

        try {
            $places = Currency::query()->where('name', $this->get())->value('decimal_places');
        } catch (Throwable) {
            $places = null;
        }

        return $this->resolvedDecimals = $places === null ? 2 : (int) $places;
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
