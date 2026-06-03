<?php

namespace Webkul\Assets\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Webkul\Assets\Database\Factories\AssetFactory;
use Webkul\Assets\Enums\AssetStatus;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

class Asset extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'asset_number',
        'name',
        'description',
        'category',
        'serial_number',
        'status',
        'value',
        'location',
        'plate_number',
        'registration_number',
        'mileage',
        'purchased_at',
        'notes',
        'company_id',
        'creator_id',
    ];

    protected function casts(): array
    {
        return [
            'status'       => AssetStatus::class,
            'value'        => 'decimal:3',
            'purchased_at' => 'date',
        ];
    }

    public function getModelTitle(): string
    {
        return __('assets::assets.models.asset');
    }

    public function borrowings(): HasMany
    {
        return $this->hasMany(AssetBorrowing::class);
    }

    public function activeBorrowing(): HasOne
    {
        return $this->hasOne(AssetBorrowing::class)
            ->whereIn('status', ['active', 'overdue'])
            ->latestOfMany('borrowed_at');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', AssetStatus::Available);
    }

    public function scopeBorrowed(Builder $query): Builder
    {
        return $query->where('status', AssetStatus::Borrowed);
    }

    public function canBeBorrowed(): bool
    {
        return $this->status === AssetStatus::Available;
    }

    public function isBorrowed(): bool
    {
        return $this->status === AssetStatus::Borrowed;
    }

    protected static function newFactory(): AssetFactory
    {
        return AssetFactory::new();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Asset $asset): void {
            $asset->creator_id ??= Auth::id();
            $asset->company_id ??= Auth::user()?->default_company_id;
            $asset->status ??= AssetStatus::Available;
            $asset->asset_number ??= static::nextAssetNumber(now()->year);
        });
    }

    protected static function nextAssetNumber(int $year): string
    {
        return DB::transaction(function () use ($year): string {
            $latestNumber = static::query()
                ->where('asset_number', 'like', "AST-{$year}-%")
                ->lockForUpdate()
                ->max('asset_number');

            $sequence = $latestNumber ? ((int) substr($latestNumber, -4)) + 1 : 1;

            return sprintf('AST-%d-%04d', $year, $sequence);
        });
    }
}
