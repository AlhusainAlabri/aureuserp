<?php

namespace App\Filament\Extensions;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Webkul\Employee\Models\Employee;
use Webkul\TimeOff\Models\Leave;

class TimeOffResourceExtensions
{
    /** @return array<int, mixed> */
    public static function substituteFormSection(): array
    {
        $tableName = self::resolveLeaveTableName();

        if ($tableName === null || ! Schema::hasColumn($tableName, 'substitute_employee_id')) {
            return [];
        }

        return [
            Section::make(__('hr-extensions::leave.substitute_section'))
                ->schema([
                    Select::make('substitute_employee_id')
                        ->label(__('hr-extensions::leave.substitute_employee'))
                        ->helperText(__('hr-extensions::leave.substitute_helper'))
                        ->options(fn (): array => Employee::query()
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->required(fn (): bool => (bool) config('hr-extensions.require_substitute', true)),
                    Textarea::make('substitute_notes')
                        ->label(__('hr-extensions::leave.handover_notes'))
                        ->helperText(__('hr-extensions::leave.handover_helper'))
                        ->maxLength(1000)
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(1)
                ->columnSpanFull()
                ->visible(fn (): bool => config('hr-extensions.require_substitute', true)),
        ];
    }

    /** @return array<int, mixed> */
    public static function substituteInfolistSection(): array
    {
        $tableName = self::resolveLeaveTableName();

        if ($tableName === null || ! Schema::hasColumn($tableName, 'substitute_employee_id')) {
            return [];
        }

        return [
            Section::make(__('hr-extensions::leave.substitute_section'))
                ->schema([
                    TextEntry::make('substituteEmployee.name')
                        ->label(__('hr-extensions::leave.substitute_employee')),
                    TextEntry::make('substitute_status')
                        ->label(__('hr-extensions::leave.infolist.substitute_status'))
                        ->state(fn (Leave $record): string => self::substituteStatusLabel($record))
                        ->badge()
                        ->color(fn (Leave $record): string => self::substituteStatusColor($record)),
                    TextEntry::make('substitute_notes')
                        ->label(__('hr-extensions::leave.handover_notes'))
                        ->visible(fn (Leave $record): bool => filled($record->substitute_notes)),
                ])
                ->columns(2)
                ->columnSpanFull()
                ->visible(fn (Leave $record): bool => filled($record->substitute_employee_id)),
        ];
    }

    public static function substituteStatusLabel(Leave $record): string
    {
        if ($record->substitute_accepted_at) {
            return __('hr-extensions::leave.substitute_accepted');
        }

        if ($record->substitute_declined_at) {
            return __('hr-extensions::leave.substitute_declined');
        }

        return __('hr-extensions::leave.substitute_pending');
    }

    public static function substituteStatusColor(Leave $record): string
    {
        if ($record->substitute_accepted_at) {
            return 'success';
        }

        if ($record->substitute_declined_at) {
            return 'danger';
        }

        return 'warning';
    }

    public static function substituteEmployeeQuery(Builder $query, ?int $excludeEmployeeId = null): Builder
    {
        $query->where('is_active', true);

        if ($excludeEmployeeId) {
            $query->where('id', '!=', $excludeEmployeeId);
        }

        return $query;
    }

    protected static function resolveLeaveTableName(): ?string
    {
        foreach (['time_off_leaves', 'hr_leaves', 'leaves'] as $tableName) {
            if (Schema::hasTable($tableName)) {
                return $tableName;
            }
        }

        return null;
    }
}
