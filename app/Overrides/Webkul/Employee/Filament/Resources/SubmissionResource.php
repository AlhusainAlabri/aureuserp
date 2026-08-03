<?php

namespace Webkul\Employee\Filament\Resources;

use App\Support\Dashboard\DashboardMetricCache;
use Webkul\Employee\Models\EmployeeSubmission;

class SubmissionResource extends CoreSubmissionResource
{
    public static function getNavigationBadge(): ?string
    {
        $count = DashboardMetricCache::rememberGlobal(
            'submissions.open_count',
            fn (): int => (int) EmployeeSubmission::open()->count(),
            60,
        );

        return (string) $count;
    }
}
