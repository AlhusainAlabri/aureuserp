<?php

namespace Webkul\Correspondence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Correspondence\Database\Factories\DepartmentFactory;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'manager_id',
        'company_id',
    ];

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(User::class, 'id', 'manager_id');
    }

    public function correspondences(): HasMany
    {
        return $this->hasMany(Correspondence::class, 'from_department_id')
            ->orWhere('to_department_id', $this->id);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    protected static function newFactory(): DepartmentFactory
    {
        return DepartmentFactory::new();
    }
}
