<?php

namespace App\Filament\Extensions;

use App\Filament\Resources\EmployeeResource\Pages\EditEmployee;
use App\Filament\Resources\EmployeeResource\Pages\ManageCompensation;
use App\Filament\Resources\EmployeeResource\Pages\ManageContracts;
use App\Filament\Resources\EmployeeResource\Pages\ManageDocuments;
use App\Filament\Resources\EmployeeResource\Pages\ManagePayslipHistory;
use App\Filament\Resources\EmployeeResource\Pages\ManageSalaryRaises;
use App\Filament\Resources\EmployeeResource\Pages\ManageSelfAssessments;
use App\Filament\Resources\EmployeeResource\Pages\ManageTrainings;
use App\Filament\Resources\EmployeeResource\Pages\ManageWarnings;
use App\Filament\Resources\EmployeeResource\Pages\ViewEmployee;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Webkul\Employee\Filament\Resources\EmployeeResource\Pages\ManageMeetings;
use Webkul\Employee\Filament\Resources\EmployeeResource\Pages\ManageResume;
use Webkul\Employee\Filament\Resources\EmployeeResource\Pages\ManageSkill;
use Webkul\Employee\Filament\Resources\EmployeeResource\Pages\OverviewEmployee;
use Webkul\Employee\Models\Department;

class EmployeeResourceExtensions
{
    /** @return array<int, mixed> */
    public static function departmentsFormSection(): array
    {
        if (! Schema::hasTable('department_employee')) {
            return [];
        }

        return [
            Section::make(__('hr-extensions::employee.sections.departments'))
                ->schema([
                    Select::make('departments')
                        ->label(__('hr-extensions::employee.all_departments'))
                        ->multiple()
                        ->relationship(
                            name: 'departments',
                            titleAttribute: 'complete_name',
                            modifyQueryUsing: fn ($query) => $query->orderBy('complete_name'),
                        )
                        ->getOptionLabelFromRecordUsing(fn (Department $record): string => $record->complete_name ?? $record->name)
                        ->searchable()
                        ->preload()
                        ->live()
                        ->required()
                        ->dehydrated(false)
                        ->afterStateUpdated(function (Set $set, ?array $state): void {
                            if (is_array($state) && count($state) > 0) {
                                $set('department_id', $state[0]);
                            }
                        }),
                    Select::make('department_id')
                        ->label(__('hr-extensions::employee.primary_department'))
                        ->options(fn (Get $get): array => Department::query()
                            ->whereIn('id', $get('departments') ?? [])
                            ->pluck('name', 'id')
                            ->all())
                        ->visible(fn (Get $get): bool => count($get('departments') ?? []) > 1)
                        ->required(fn (Get $get): bool => count($get('departments') ?? []) > 1),
                ])
                ->columns(1)
                ->columnSpanFull(),
        ];
    }

    /** @return array<int, mixed> */
    public static function employmentFormSection(): array
    {
        if (! Schema::hasColumn('employees_employees', 'primary_job_responsibilities')) {
            return [];
        }

        return [
            Section::make(__('hr-extensions::employee.sections.employment'))
                ->schema([
                    Textarea::make('primary_job_responsibilities')
                        ->label(__('hr-extensions::employee.fields.primary_job_responsibilities'))
                        ->rows(4)
                        ->maxLength(5000)
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ];
    }

    /** @return array<int, TextColumn> */
    public static function extraTableColumns(): array
    {
        if (! Schema::hasTable('department_employee')) {
            return [];
        }

        return [
            TextColumn::make('departments.name')
                ->badge()
                ->separator(', ')
                ->label(__('hr-extensions::employee.all_departments'))
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    /** @return array<int, TernaryFilter> */
    public static function extraTableFilters(): array
    {
        if (! Schema::hasColumn('employees_employees', 'is_closed')) {
            return [];
        }

        return [
            TernaryFilter::make('is_closed')
                ->label(__('hr-extensions::employee.show_closed_files'))
                ->default(false)
                ->queries(
                    true: fn (Builder $query) => $query->where('is_closed', true),
                    false: fn (Builder $query) => $query->where('is_closed', false),
                    blank: fn (Builder $query) => $query->where('is_closed', false),
                ),
        ];
    }

    /** @return array<string, mixed> */
    public static function extraPages(): array
    {
        return [
            'documents'          => ManageDocuments::route('/{record}/documents'),
            'warnings'           => ManageWarnings::route('/{record}/warnings'),
            'contracts'          => ManageContracts::route('/{record}/contracts'),
            'trainings'          => ManageTrainings::route('/{record}/trainings'),
            'salary_raises'      => ManageSalaryRaises::route('/{record}/salary-raises'),
            'compensation'       => ManageCompensation::route('/{record}/compensation'),
            'payslip_history'    => ManagePayslipHistory::route('/{record}/payslip-history'),
            'self_assessments'   => ManageSelfAssessments::route('/{record}/self-assessments'),
        ];
    }

    /** @return array<int, Page> */
    public static function extraSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            OverviewEmployee::class,
            ViewEmployee::class,
            EditEmployee::class,
            ManageSkill::class,
            ManageResume::class,
            ManageDocuments::class,
            ManageContracts::class,
            ManageWarnings::class,
            ManageMeetings::class,
            ManageTrainings::class,
            ManageCompensation::class,
            ManageSalaryRaises::class,
            ManagePayslipHistory::class,
            ManageSelfAssessments::class,
        ]);
    }

    /** @return array<int, mixed> */
    public static function fileStatusInfolistSection(): array
    {
        if (! Schema::hasColumn('employees_employees', 'is_closed')) {
            return [];
        }

        return [
            Section::make(__('hr-extensions::employee.sections.file_status'))
                ->schema([
                    TextEntry::make('is_closed')
                        ->label(__('hr-extensions::employee.file_status.closed'))
                        ->formatStateUsing(fn (bool $state): string => $state
                            ? __('hr-extensions::employee.yes')
                            : __('hr-extensions::employee.no'))
                        ->badge()
                        ->color(fn (bool $state): string => $state ? 'danger' : 'success'),
                    TextEntry::make('closure_reason')
                        ->label(__('hr-extensions::employee.file_status.reason'))
                        ->formatStateUsing(fn (?string $state): ?string => $state
                            ? __('hr-extensions::employee.closure_reasons.'.$state)
                            : null)
                        ->visible(fn ($record): bool => (bool) $record->is_closed),
                    TextEntry::make('closure_notes')
                        ->label(__('hr-extensions::employee.file_status.notes'))
                        ->visible(fn ($record): bool => (bool) $record->is_closed),
                    TextEntry::make('closed_at')
                        ->label(__('hr-extensions::employee.file_status.closed_at'))
                        ->dateTime()
                        ->visible(fn ($record): bool => (bool) $record->is_closed),
                    TextEntry::make('closedBy.name')
                        ->label(__('hr-extensions::employee.file_status.closed_by'))
                        ->visible(fn ($record): bool => (bool) $record->is_closed),
                    TextEntry::make('reopen_reason')
                        ->label(__('hr-extensions::employee.file_status.reopen_reason'))
                        ->visible(fn ($record): bool => filled($record->reopen_reason)),
                    TextEntry::make('reopened_at')
                        ->label(__('hr-extensions::employee.file_status.reopened_at'))
                        ->dateTime()
                        ->visible(fn ($record): bool => filled($record->reopened_at)),
                    TextEntry::make('reopenedBy.name')
                        ->label(__('hr-extensions::employee.file_status.reopened_by'))
                        ->visible(fn ($record): bool => filled($record->reopened_at)),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ];
    }
}
