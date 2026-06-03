<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Security\Models\User;

class InventoryReportArchive extends Model
{
    protected $fillable = [
        'report_type',
        'period_from',
        'period_to',
        'file_path',
        'file_format',
        'generated_by',
        'filters',
    ];

    protected function casts(): array
    {
        return [
            'period_from' => 'date',
            'period_to'   => 'date',
            'filters'     => 'array',
        ];
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
