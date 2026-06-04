<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\Dashboard\Concerns\BuildsEmptyTableQueries;
use App\Services\Projects\UnifiedTaskQueryService;
use App\Support\FilamentUrl;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget as BaseWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Webkul\Employee\Models\Employee;
use Webkul\TimeOff\Models\Leave;

class MyTasksTodayWidget extends BaseWidget
{
    use BuildsEmptyTableQueries;
    use InteractsWithPageFilters;

    protected static ?int $sort = 18;

    protected static bool $isLazy = true;

    protected string $view = 'filament.widgets.dashboard.my-tasks-today';

    public function getColumnSpan(): int|string|array
    {
        if (auth()->check() && in_array(static::class, Dashboard::getCommandCenterStatWidgets(), true)) {
            return 12;
        }

        return [
            'default' => 12,
            'lg'      => 4,
        ];
    }

    public function getTableHeading(): string|Htmlable|null
    {
        return __('dashboard.widgets.my_tasks');
    }

    protected function getViewData(): array
    {
        return [
            'substituteCoverages' => $this->getSubstituteCoverages(),
            'tasks'               => UnifiedTaskQueryService::myTasksToday()
                ->map(fn (array $task): array => array_merge($task, [
                    'url' => filled($task['url'] ?? null)
                        ? FilamentUrl::appendLocaleToUrl($task['url'])
                        : null,
                ])),
        ];
    }

    /** @return Collection<int, Leave> */
    public function getSubstituteCoverages(): Collection
    {
        if (! class_exists(Leave::class) || ! Schema::hasTable('time_off_leaves')) {
            return collect();
        }

        if (! Schema::hasColumn('time_off_leaves', 'substitute_employee_id')) {
            return collect();
        }

        $employee = auth()->user()?->employee;

        if (! $employee instanceof Employee) {
            return collect();
        }

        return Leave::query()
            ->with('employee')
            ->where('substitute_employee_id', $employee->id)
            ->whereNotNull('substitute_accepted_at')
            ->whereDate('date_from', '<=', now())
            ->whereDate('date_to', '>=', now())
            ->orderBy('date_from')
            ->get();
    }
}
