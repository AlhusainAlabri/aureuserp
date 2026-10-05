<?php

namespace App\Filament\TimeOff\Concerns;

use App\Filament\Extensions\TimeOffResourceExtensions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Webkul\Employee\Models\Employee;
use Webkul\TimeOff\Enums\RequestDateFromPeriod;

/**
 * Calendar create/edit modals nest form state under mountedActions.
 * Setting non-field state (e.g. duration_info via $set) during ->live()
 * updates can corrupt mountedActions (dropping "name"), which Filament
 * then clears — closing the modal while the user is filling the form.
 */
trait StabilizesLeaveCalendarForm
{
    public function getFormSchema($isVisible = null): array
    {
        return [
            Section::make()
                ->schema([
                    Select::make('employee_id')
                        ->relationship('employee', 'name')
                        ->searchable()
                        ->preload()
                        ->live()
                        ->visible(fn (Get $get): bool => (bool) ($isVisible ?? false))
                        ->afterStateUpdated(function (Set $set, $state): void {
                            if ($state) {
                                $employee = Employee::find($state);

                                $set('department_id', $employee?->department?->id);
                            }
                        })
                        ->label(__('time-off::filament/clusters/management/resources/time-off.form.fields.employee-name'))
                        ->required(),
                    Select::make('department_id')
                        ->relationship('department', 'name')
                        ->label(__('time-off::filament/clusters/management/resources/time-off.form.fields.department-name'))
                        ->searchable()
                        ->visible(fn (Get $get): bool => (bool) ($isVisible ?? false))
                        ->preload()
                        ->required(),

                    Select::make('holiday_status_id')
                        ->label(__('time-off::filament/widgets/calendar-widget.form.fields.time-off-type'))
                        ->relationship('holidayStatus', 'name')
                        ->required()
                        ->columnSpanFull()
                        ->placeholder(__('time-off::filament/widgets/calendar-widget.form.fields.time-off-type-placeholder'))
                        ->helperText(__('time-off::filament/widgets/calendar-widget.form.fields.time-off-type-helper')),

                    Grid::make(2)
                        ->schema([
                            DatePicker::make('request_date_from')
                                ->native(false)
                                ->label(__('time-off::filament/widgets/calendar-widget.form.fields.request-date-from'))
                                ->required()
                                ->live()
                                ->prefixIcon('heroicon-o-calendar')
                                ->afterStateUpdated(function ($state, Set $set): void {
                                    if (blank($state)) {
                                        $set('request_date_to', null);
                                    }
                                }),

                            DatePicker::make('request_date_to')
                                ->native(false)
                                ->label(__('time-off::filament/widgets/calendar-widget.form.fields.request-date-to'))
                                ->hidden(fn (Get $get): bool => (bool) $get('request_unit_half'))
                                ->required(fn (Get $get): bool => ! $get('request_unit_half'))
                                ->live()
                                ->prefixIcon('heroicon-o-calendar')
                                ->disabled(fn (Get $get): bool => blank($get('request_date_from')))
                                ->minDate(fn (Get $get) => $get('request_date_from')),
                        ]),

                    Grid::make(2)
                        ->schema([
                            Toggle::make('request_unit_half')
                                ->live()
                                ->label(__('time-off::filament/widgets/calendar-widget.form.fields.half-day'))
                                ->helperText(__('time-off::filament/widgets/calendar-widget.form.fields.half-day-helper')),

                            Select::make('request_date_from_period')
                                ->label(__('time-off::filament/widgets/calendar-widget.form.fields.period'))
                                ->options(RequestDateFromPeriod::class)
                                ->default(RequestDateFromPeriod::MORNING)
                                ->native(false)
                                ->visible(fn (Get $get): bool => (bool) $get('request_unit_half'))
                                ->required(fn (Get $get): bool => (bool) $get('request_unit_half'))
                                ->prefixIcon('heroicon-o-sun'),
                        ]),

                    Placeholder::make('duration_info')
                        ->label(__('time-off::filament/widgets/calendar-widget.form.fields.duration'))
                        ->content(fn (Get $get): string => $this->resolveLeaveDurationLabel($get))
                        ->extraAttributes(['class' => 'font-medium text-primary-600 bg-primary-50 p-3 rounded-lg border border-primary-200']),

                    Textarea::make('private_name')
                        ->label(__('time-off::filament/widgets/calendar-widget.form.fields.description'))
                        ->placeholder(__('time-off::filament/widgets/calendar-widget.form.fields.description-placeholder'))
                        ->rows(3)
                        ->columnSpanFull()
                        ->helperText(__('time-off::filament/widgets/calendar-widget.form.fields.description-helper')),
                    ...TimeOffResourceExtensions::substituteFormSection(),
                ])->columnSpanFull(),
        ];
    }

    protected function resolveLeaveDurationLabel(Get $get): string
    {
        if ($get('request_unit_half')) {
            return $this->getDurationInfo([
                'request_unit_half' => true,
            ])['duration_display'];
        }

        if (blank($get('request_date_from'))) {
            return __('time-off::filament/widgets/calendar-widget.form.fields.please-select-dates');
        }

        return $this->getDurationInfo([
            'request_unit_half' => false,
            'request_date_from' => $get('request_date_from'),
            'request_date_to'   => $get('request_date_to'),
        ])['duration_display'];
    }
}
