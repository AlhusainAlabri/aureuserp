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

    public function scopeInForce(Builder $query, CarbonInterface $date): Builder
    {
        return $query
            ->whereDate('effective_from', '<=', $date->toDateString())
            ->where(function (Builder $inner) use ($date): void {
                $inner->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $date->toDateString());
            });
    }

    public function overlapsAnotherRule(): bool
    {
        $from = Carbon::parse($this->effective_from)->toDateString();
        $to = $this->effective_to === null ? null : Carbon::parse($this->effective_to)->toDateString();
        $category = $this->category instanceof PasiCategory ? $this->category->value : $this->category;

        return static::query()
            ->where('category', $category)
            ->when($this->exists, fn (Builder $query) => $query->whereKeyNot($this->getKey()))
            ->when($to !== null, fn (Builder $query) => $query->whereDate('effective_from', '<=', $to))
            ->where(function (Builder $query) use ($from): void {
                $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $from);
            })
            ->exists();
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

        static::saving(function (self $rule): void {
            if ($rule->salary_cap !== null && (float) $rule->salary_cap <= 0) {
                throw ValidationException::withMessages([
                    'salary_cap' => __('payroll::payroll.pasi.cap_positive'),
                ]);
            }

            if ($rule->overlapsAnotherRule()) {
                throw ValidationException::withMessages([
                    'effective_from' => __('payroll::payroll.pasi.overlap'),
                ]);
            }
        });
    }
}
