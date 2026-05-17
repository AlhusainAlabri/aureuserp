<?php

namespace Webkul\Employee\Models;

use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Employee\Database\Factories\EmployeeSubmissionReplyFactory;
use Webkul\Security\Models\User;

class EmployeeSubmissionReply extends Model
{
    use HasFactory;

    protected $table = 'employees_employee_submission_replies';

    protected $fillable = [
        'submission_id',
        'body',
        'is_internal',
        'replied_by',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(EmployeeSubmission::class, 'submission_id');
    }

    public function repliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replied_by');
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function (self $reply) {
            $submission = $reply->submission;

            if (! $reply->is_internal && $submission && $submission->status === 'open') {
                $submission->update(['status' => 'under_review']);
            }

            if (! $reply->is_internal && $submission && $submission->employee?->user) {
                Notification::make()
                    ->title(__('employees::filament/resources/submission.notifications.reply.title'))
                    ->body(__('employees::filament/resources/submission.notifications.reply.body', ['ticket' => $submission->ticket_number]))
                    ->info()
                    ->sendToDatabase($submission->employee->user);
            }
        });
    }

    protected static function newFactory(): EmployeeSubmissionReplyFactory
    {
        return EmployeeSubmissionReplyFactory::new();
    }
}
