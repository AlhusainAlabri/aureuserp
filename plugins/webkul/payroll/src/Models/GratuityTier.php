<?php

namespace Webkul\Payroll\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Webkul\Payroll\Database\Factories\GratuityTierFactory;
use Webkul\Security\Models\User;

class GratuityTier extends Model
{
    use HasFactory;

    protected $table = 'payroll_gratuity_tiers';

    protected $fillable = [
        'from_year',
        'to_year',
        'days_per_year',
        'effective_from',
        'effective_to',
        'creator_id',
    ];

    protected function casts(): array
    {
        return [
            'from_year'      => 'decimal:2',
            'to_year'        => 'decimal:2',
            'days_per_year'  => 'decimal:2',
            'effective_from' => 'date',
            'effective_to'   => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function scopeInForce(Builder $query, CarbonInterface $date): Builder
    {
        return $query
            ->whereDate('effective_from', '<=', $date->toDateString())
            ->where(function (Builder $inner) use ($date): void {
                $inner->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $date->toDateString());
            });
    }

    protected static function newFactory(): GratuityTierFactory
    {
        return GratuityTierFactory::new();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $tier): void {
            $tier->creator_id ??= Auth::id();
        });
    }
}
