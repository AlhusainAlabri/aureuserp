<?php

namespace Webkul\Payroll\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Webkul\Payroll\Database\Factories\PasiRuleFactory;
use Webkul\Payroll\Enums\PasiCategory;
use Webkul\Security\Models\User;

class PasiRule extends Model
{
    use HasFactory;

    protected $table = 'payroll_pasi_rules';

    protected $fillable = [
        'category',
        'employee_rate',
        'employer_rate',
        'salary_cap',
        'effective_from',
        'effective_to',
        'creator_id',
    ];

    protected function casts(): array
    {
        return [
            'category'       => PasiCategory::class,
            'employee_rate'  => 'decimal:2',
            'employer_rate'  => 'decimal:2',
            'salary_cap'     => 'decimal:3',
            'effective_from' => 'date',
            'effective_to'   => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function scopeInForce(Builder $query, Carbon $date): Builder
    {
        return $query
            ->whereDate('effective_from', '<=', $date->toDateString())
            ->where(function (Builder $inner) use ($date): void {
                $inner->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $date->toDateString());
            });
    }

    protected static function newFactory(): PasiRuleFactory
    {
        return PasiRuleFactory::new();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $rule): void {
            $rule->creator_id ??= Auth::id();
        });
    }
}
