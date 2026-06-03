<?php

namespace App\Console\Commands;

use App\Enums\Hr\SelfAssessmentStatus;
use App\Mail\SelfAssessmentReminderMail;
use App\Models\Hr\EmployeeSelfAssessment;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Webkul\Employee\Models\Employee;

class RemindSelfAssessments extends Command
{
    protected $signature = 'hr:remind-self-assessments';

    protected $description = 'Remind employees to submit monthly self-assessments';

    public function handle(): int
    {
        if (! Schema::hasTable('employee_self_assessments')) {
            return self::SUCCESS;
        }

        $now = Carbon::now();
        $year = (int) $now->year;
        $month = (int) $now->month;

        $employees = Employee::query()
            ->where('is_active', true)
            ->whereNotNull('user_id')
            ->with('user')
            ->get();

        $reminded = 0;

        foreach ($employees as $employee) {
            $exists = EmployeeSelfAssessment::query()
                ->where('employee_id', $employee->id)
                ->where('period_year', $year)
                ->where('period_month', $month)
                ->whereIn('status', [SelfAssessmentStatus::Submitted, SelfAssessmentStatus::Reviewed])
                ->exists();

            if ($exists || ! $employee->user) {
                continue;
            }

            Notification::make()
                ->title(__('hr-extensions::self_assessment.notifications.reminder_title'))
                ->body(__('hr-extensions::self_assessment.notifications.reminder_body', [
                    'month' => __('hr-extensions::self_assessment.months.'.$month),
                    'year'  => $year,
                ]))
                ->warning()
                ->sendToDatabase($employee->user);

            Mail::queue(new SelfAssessmentReminderMail($employee, $year, $month));

            $reminded++;
        }

        $this->info("Reminded {$reminded} employees.");

        return self::SUCCESS;
    }
}
