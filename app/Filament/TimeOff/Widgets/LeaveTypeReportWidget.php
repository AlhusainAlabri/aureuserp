<?php

namespace App\Filament\TimeOff\Widgets;

use App\Filament\Concerns\InheritsWidgetShieldPermission;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Webkul\TimeOff\Enums\State;
use Webkul\TimeOff\Filament\Widgets\LeaveTypeWidget as BaseLeaveTypeWidget;
use Webkul\TimeOff\Models\Leave;

class LeaveTypeReportWidget extends BaseLeaveTypeWidget
{
    use InheritsWidgetShieldPermission;
    use InteractsWithPageFilters;

    protected function getData(): array
    {
        $query = Leave::query();

        if ($this->pageFilters['selectedCompanies'] ?? null) {
            $query->whereIn('company_id', $this->pageFilters['selectedCompanies']);
        }

        if ($this->pageFilters['selectedDepartments'] ?? null) {
            $query->whereIn('department_id', $this->pageFilters['selectedDepartments']);
        }

        if ($this->pageFilters['startDate'] ?? null) {
            $query->where('request_date_from', '>=', Carbon::parse($this->pageFilters['startDate'])->startOfDay());
        }

        if ($this->pageFilters['endDate'] ?? null) {
            $query->where('request_date_from', '<=', Carbon::parse($this->pageFilters['endDate'])->endOfDay());
        }

        $confirm = State::CONFIRM->value;
        $validateOne = State::VALIDATE_ONE->value;
        $validateTwo = State::VALIDATE_TWO->value;
        $refuse = State::REFUSE->value;

        $stats = $query->selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN state = '{$confirm}' THEN 1 ELSE 0 END) as confirm,
            SUM(CASE WHEN state = '{$validateOne}' THEN 1 ELSE 0 END) as validate_one,
            SUM(CASE WHEN state = '{$validateTwo}' THEN 1 ELSE 0 END) as validate_two,
            SUM(CASE WHEN state = '{$refuse}' THEN 1 ELSE 0 END) as refuse
        ")->first();

        $labels = [
            'confirm'      => State::CONFIRM->getLabel(),
            'validate_one' => State::VALIDATE_ONE->getLabel(),
            'validate_two' => State::VALIDATE_TWO->getLabel(),
            'refuse'       => State::REFUSE->getLabel(),
        ];

        $data = match ($this->pageFilters['status'] ?? 'all') {
            State::CONFIRM->value      => [$labels['confirm'] => $stats->confirm ?? 0],
            State::VALIDATE_ONE->value => [$labels['validate_one'] => $stats->validate_one ?? 0],
            State::VALIDATE_TWO->value => [$labels['validate_two'] => $stats->validate_two ?? 0],
            State::REFUSE->value       => [$labels['refuse'] => $stats->refuse ?? 0],
            default                    => [
                $labels['confirm']      => $stats->confirm ?? 0,
                $labels['validate_one'] => $stats->validate_one ?? 0,
                $labels['validate_two'] => $stats->validate_two ?? 0,
                $labels['refuse']       => $stats->refuse ?? 0,
            ],
        };

        $colors = [
            $labels['confirm']      => '#3b82f6',
            $labels['validate_one'] => '#6366f1',
            $labels['validate_two'] => '#22c55e',
            $labels['refuse']       => '#ef4444',
        ];

        return [
            'datasets' => [
                [
                    'label'           => __('time-off::filament/widgets/leave-type-widget.label'),
                    'data'            => array_values($data),
                    'backgroundColor' => array_map(
                        fn (string $label): string => $colors[$label] ?? '#94a3b8',
                        array_keys($data),
                    ),
                ],
            ],
            'labels' => array_keys($data),
        ];
    }
}
