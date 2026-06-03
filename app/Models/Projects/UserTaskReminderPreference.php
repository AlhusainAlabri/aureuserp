<?php

namespace App\Models\Projects;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Security\Models\User;

class UserTaskReminderPreference extends Model
{
    protected $fillable = [
        'user_id',
        'notify_same_day',
        'notify_one_day_before',
        'notify_three_days_before',
        'notify_seven_days_before',
        'email_enabled',
        'database_enabled',
    ];

    protected $casts = [
        'notify_same_day'          => 'boolean',
        'notify_one_day_before'    => 'boolean',
        'notify_three_days_before' => 'boolean',
        'notify_seven_days_before' => 'boolean',
        'email_enabled'            => 'boolean',
        'database_enabled'         => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return array<int, int> */
    public function reminderOffsetsInDays(): array
    {
        $offsets = [];

        if ($this->notify_seven_days_before) {
            $offsets[] = 7;
        }

        if ($this->notify_three_days_before) {
            $offsets[] = 3;
        }

        if ($this->notify_one_day_before) {
            $offsets[] = 1;
        }

        if ($this->notify_same_day) {
            $offsets[] = 0;
        }

        return array_values(array_unique($offsets));
    }
}
