<?php

namespace Webkul\Employee\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Employee\Database\Factories\EmployeeDocumentFactory;
use Webkul\Security\Models\User;

class EmployeeDocument extends Model
{
    use HasFactory;

    protected $table = 'employees_employee_documents';

    protected $fillable = [
        'employee_id',
        'document_type',
        'document_name',
        'file_path',
        'expiry_date',
        'notes',
        'notified_at',
        'creator_id',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'notified_at' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function isExpired(): bool
    {
        return $this->expiry_date !== null && $this->expiry_date->isPast();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        if ($this->expiry_date === null) {
            return false;
        }

        return $this->expiry_date->isFuture() && $this->expiry_date->diffInDays(now()) <= $days;
    }

    protected static function newFactory(): EmployeeDocumentFactory
    {
        return EmployeeDocumentFactory::new();
    }
}
