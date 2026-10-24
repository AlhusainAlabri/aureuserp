<?php

namespace App\Support\Accounting;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Numbers accounting documents per journal and year ("INV/2026/00001").
 *
 * The number used to be the database id, which shared one counter across every
 * journal and skipped numbers whenever another record type was created. This
 * continues from the highest number already used for the prefix, in moves and
 * in payments, so a number in use is never reused.
 */
class DocumentSequence
{
    public const PAD = 5;

    /**
     * Next full name for a prefix such as "INV/2026".
     */
    public static function next(string $prefix, ?int $ignoreMoveId = null): string
    {
        return Cache::lock('accounting-sequence:'.$prefix, 10)->block(5, function () use ($prefix, $ignoreMoveId): string {
            $max = max(
                static::maxNumber('accounts_account_moves', $prefix, $ignoreMoveId),
                static::maxNumber('accounts_account_payments', $prefix),
            );

            return $prefix.'/'.str_pad((string) ($max + 1), static::PAD, '0', STR_PAD_LEFT);
        });
    }

    protected static function maxNumber(string $table, string $prefix, ?int $ignoreId = null): int
    {
        $query = DB::table($table)->where('name', 'like', $prefix.'/%');

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return (int) $query
            ->selectRaw("MAX(CAST(SUBSTRING_INDEX(name, '/', -1) AS UNSIGNED)) as number")
            ->value('number');
    }
}
