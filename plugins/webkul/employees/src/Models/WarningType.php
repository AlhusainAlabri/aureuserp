<?php

namespace Webkul\Employee\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Webkul\Employee\Database\Factories\WarningTypeFactory;
use Webkul\Field\Traits\HasCustomFields;
use Webkul\Partner\Models\Company;
use Webkul\Security\Models\User;

class WarningType extends Model implements Sortable
{
    use HasCustomFields, HasFactory, SortableTrait;

    protected $table = 'employees_warning_types';

    protected $fillable = [
        'name',
        'description',
        'creator_id',
        'company_id',
        'sort',
    ];

    public $sortable = [
        'order_column_name'  => 'sort',
        'sort_when_creating' => true,
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function warnings(): HasMany
    {
        return $this->hasMany(EmployeeWarning::class, 'warning_type_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($warningType) {
            $warningType->creator_id ??= Auth::id();
        });
    }

    protected static function newFactory(): WarningTypeFactory
    {
        return WarningTypeFactory::new();
    }
}
