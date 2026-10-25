<?php

namespace Webkul\Payroll\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
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

    /**
     * Same-period tiers must not share years: bands are half-open (0-3 and 3-open are adjacent, not overlapping).
     */
    public function overlapsAnotherTier(): bool
    {
        $from = (float) $this->from_year;
        $to = $this->to_year === null ? null : (float) $this->to_year;
        $effectiveFrom = Carbon::parse($this->effective_from)->toDateString();
        $effectiveTo = $this->effective_to === null ? null : Carbon::parse($this->effective_to)->toDateString();

        return static::query()
            ->when($this->exists, fn (Builder $query) => $query->whereKeyNot($this->getKey()))
            ->when($effectiveTo !== null, fn (Builder $query) => $query->whereDate('effective_from', '<=', $effectiveTo))
            ->where(function (Builder $query) use ($effectiveFrom): void {
                $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $effectiveFrom);
            })
            ->when($to !== null, fn (Builder $query) => $query->where('from_year', '<', $to))
            ->where(function (Builder $query) use ($from): void {
                $query->whereNull('to_year')->orWhere('to_year', '>', $from);
            })
            ->exists();
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

        static::saving(function (self $tier): void {
            if ($tier->overlapsAnotherTier()) {
                throw ValidationException::withMessages([
                    'from_year' => __('payroll::payroll.gratuity.tier_overlap'),
                ]);
            }
        });
    }
}
