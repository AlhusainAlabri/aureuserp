<?php

namespace App\Models\Projects;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Project\Models\Task;
use Webkul\Support\Models\Company;

class TaskCategory extends Model
{
    protected $table = 'projects_task_categories';

    protected $fillable = [
        'name',
        'color',
        'is_active',
        'company_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'category_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
