<?php

namespace App\Filament\TimeOff\Pages;

use App\Filament\Concerns\InteractsWithAdvancedDashboard;
use App\Filament\TimeOff\Widgets\LeaveTypeReportWidget;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Webkul\Employee\Models\Department;
use Webkul\Support\Models\Company;
use Webkul\TimeOff\Enums\State;
use Webkul\TimeOff\Filament\Pages\ByType as BaseByTypeReport;

class ExtendedByTypeReport extends BaseByTypeReport
{
    use BaseDashboard\Concerns\HasFiltersForm;
    use InteractsWithAdvancedDashboard;

    protected string $view = 'filament.pages.advanced-dashboard';

    public function getSubheading(): ?string
    {
        return __('time-off::filament/pages/by-type.subheading');
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            $this->configureFilterSection(
                Section::make()
                    ->schema([
                        Select::make('selectedCompanies')
                            ->label(__('time-off::filament/pages/by-type.filters.companies'))
                            ->multiple()
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => Company::query()
                                ->where('name', 'like', "%{$search}%")
                                ->limit(50)
                                ->pluck('name', 'id')
                                ->all())
                            ->getOptionLabelsUsing(fn (array $values): array => Company::query()
                                ->whereIn('id', $values)
                                ->pluck('name', 'id')
                                ->all()),
                        Select::make('selectedDepartments')
                            ->label(__('time-off::filament/pages/by-type.filters.departments'))
                            ->multiple()
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => Department::query()
                                ->where('name', 'like', "%{$search}%")
                                ->limit(50)
                                ->pluck('name', 'id')
                                ->all())
                            ->getOptionLabelsUsing(fn (array $values): array => Department::query()
                                ->whereIn('id', $values)
                                ->pluck('name', 'id')
                                ->all()),
                        Select::make('status')
                            ->label(__('time-off::filament/pages/by-type.filters.status.title'))
                            ->options([
                                'all'                      => __('time-off::filament/pages/by-type.filters.status.all'),
                                State::CONFIRM->value      => State::CONFIRM->getLabel(),
                                State::VALIDATE_ONE->value => State::VALIDATE_ONE->getLabel(),
                                State::VALIDATE_TWO->value => State::VALIDATE_TWO->getLabel(),
                                State::REFUSE->value       => State::REFUSE->getLabel(),
                            ])
                            ->default('all')
                            ->native(false),
                        DatePicker::make('startDate')
                            ->label(__('time-off::filament/pages/by-type.filters.start-date'))
                            ->maxDate(fn (Get $get) => $get('endDate') ?: now())
                            ->default(now()->subMonth()->format('Y-m-d'))
                            ->native(false),
                        DatePicker::make('endDate')
                            ->label(__('time-off::filament/pages/by-type.filters.end-date'))
                            ->minDate(fn (Get $get) => $get('startDate') ?: now())
                            ->maxDate(now())
                            ->default(now())
                            ->native(false),
                    ])
                    ->columnSpanFull()
                    ->columns([
                        'default' => 1,
                        'sm'      => 2,
                        'md'      => 3,
                        'xl'      => 5,
                    ]),
            ),
        ]);
    }

    public function getWidgets(): array
    {
        return [
            LeaveTypeReportWidget::class,
        ];
    }
}
