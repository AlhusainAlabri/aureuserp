<?php

namespace Webkul\Attendance\Filament\Resources;

use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Webkul\Attendance\Enums\DayStatus;
use Webkul\Attendance\Filament\Concerns\AppendsLocaleToResourceUrls;
use Webkul\Attendance\Filament\Resources\AttendanceDayResource\Pages\CreateAttendanceDay;
use Webkul\Attendance\Filament\Resources\AttendanceDayResource\Pages\EditAttendanceDay;
use Webkul\Attendance\Filament\Resources\AttendanceDayResource\Pages\ListAttendanceDays;
use Webkul\Attendance\Models\AttendanceDay;
use Webkul\Attendance\Services\AttendanceDayCalculator;
use Webkul\Attendance\Services\AttendanceDayCsv;
use Webkul\Attendance\Services\AttendanceScope;
use Webkul\Employee\Models\Employee;

class AttendanceDayResource extends Resource
{
    use AppendsLocaleToResourceUrls;

    /**
     * How many months back the month filter offers.
     */
    protected const MONTH_CHOICES = 36;

    protected const EMPLOYEE_SEARCH_LIMIT = 50;

    protected static ?string $model = AttendanceDay::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $slug = 'attendances/days';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return static::getPluralModelLabel();
    }

    public static function getNavigationGroup(): string
    {
        return __('attendances::attendances.navigation.group');
    }

    public static function getModelLabel(): string
    {
        return __('attendances::attendances.models.attendance_day');
    }

    public static function getPluralModelLabel(): string
    {
        return __('attendances::attendances.models_plural.attendance_day');
    }

    /**
     * Only the days of employees the signed-in user may see (HR: everyone; others: self and team).
     *
     * @return Builder<AttendanceDay>
     */
    public static function getEloquentQuery(): Builder
    {
        return AttendanceScope::applyTo(
            parent::getEloquentQuery()->with('employee'),
            Auth::user(),
        );
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('attendances::attendances.day.sections.entry'))
                ->description(__('attendances::attendances.day.entry_hint'))
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('employee_id')
                            ->label(__('attendances::attendances.day.columns.employee'))
                            ->required()
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => static::employeeQuery()
                                ->where('name', 'like', '%'.$search.'%')
                                ->limit(static::EMPLOYEE_SEARCH_LIMIT)
                                ->pluck('name', 'id')
                                ->all())
                            ->getOptionLabelUsing(fn (mixed $value): ?string => static::employeeQuery()->whereKey($value)->value('name'))
                            ->disabledOn('edit')
                            ->columnSpan(1),
                        DatePicker::make('date')
                            ->label(__('attendances::attendances.day.columns.date'))
                            ->required()
                            ->disabledOn('edit')
                            ->columnSpan(1),
                        Select::make('status')
                            ->label(__('attendances::attendances.day.columns.status'))
                            ->options(DayStatus::class)
                            ->required()
                            ->default(DayStatus::Present->value)
                            ->columnSpanFull(),
                        DateTimePicker::make('first_in')
                            ->label(__('attendances::attendances.day.columns.first_in'))
                            ->helperText(__('attendances::attendances.day.local_time_hint'))
                            ->timezone(fn (): string => (string) config('app.timezone'))
                            ->seconds(false)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => static::suggestMinutes($get, $set))
                            ->columnSpan(1),
                        DateTimePicker::make('last_out')
                            ->label(__('attendances::attendances.day.columns.last_out'))
                            ->helperText(__('attendances::attendances.day.local_time_hint'))
                            ->timezone(fn (): string => (string) config('app.timezone'))
                            ->seconds(false)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => static::suggestMinutes($get, $set))
                            ->after('first_in')
                            ->columnSpan(1),
                        TextInput::make('late_minutes')
                            ->label(__('attendances::attendances.day.columns.late'))
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->maxValue(1440)
                            ->default(0)
                            ->required()
                            ->columnSpan(1),
                        TextInput::make('early_leave_minutes')
                            ->label(__('attendances::attendances.day.columns.early_leave'))
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->maxValue(1440)
                            ->default(0)
                            ->required()
                            ->columnSpan(1),
                        TextInput::make('overtime_minutes')
                            ->label(__('attendances::attendances.day.columns.overtime'))
                            ->helperText(__('attendances::attendances.day.overtime_hint'))
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->maxValue(1440)
                            ->default(0)
                            ->required()
                            ->columnSpan(1),
                        Textarea::make('override_note')
                            ->label(__('attendances::attendances.day.columns.override_note'))
                            ->helperText(__('attendances::attendances.day.note_hint'))
                            ->required()
                            ->rows(3)
                            ->maxLength(2000)
                            ->columnSpanFull(),
                    ]),
                ]),
        ]);
    }

    /**
     * Fills the lateness, early-leave and overtime fields from the typed times; HR can still overwrite them.
     */
    protected static function suggestMinutes(Get $get, Set $set): void
    {
        $employeeId = $get('employee_id');
        $date = $get('date');
        $in = $get('first_in');
        $out = $get('last_out');

        if (blank($employeeId) || blank($date) || blank($in) || blank($out)) {
            return;
        }

        $employee = static::employeeQuery()->whereKey($employeeId)->first();

        if ($employee === null) {
            return;
        }

        $suggested = app(AttendanceDayCalculator::class)->suggest(
            $employee,
            Carbon::createFromFormat('Y-m-d', substr((string) $date, 0, 10), 'UTC')->startOfDay(),
            (string) $in,
            (string) $out,
        );

        foreach ($suggested as $field => $minutes) {
            $set($field, $minutes);
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.name')
                    ->label(__('attendances::attendances.day.columns.employee'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('date')
                    ->label(__('attendances::attendances.day.columns.date'))
                    ->formatStateUsing(fn (AttendanceDay $record): string => $record->date->translatedFormat('d M Y'))
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('attendances::attendances.day.columns.status'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('first_in')
                    ->label(__('attendances::attendances.day.columns.first_in'))
                    ->state(fn (AttendanceDay $record): string => static::localTime($record, $record->first_in))
                    ->sortable(),
                TextColumn::make('last_out')
                    ->label(__('attendances::attendances.day.columns.last_out'))
                    ->state(fn (AttendanceDay $record): string => static::localTime($record, $record->last_out))
                    ->sortable(),
                TextColumn::make('worked_minutes')
                    ->label(__('attendances::attendances.day.columns.worked'))
                    ->formatStateUsing(fn (?int $state): string => AttendanceDayCsv::formatMinutes((int) $state))
                    ->sortable(),
                TextColumn::make('late_minutes')
                    ->label(__('attendances::attendances.day.columns.late'))
                    ->sortable(),
                TextColumn::make('early_leave_minutes')
                    ->label(__('attendances::attendances.day.columns.early_leave'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('overtime_minutes')
                    ->label(__('attendances::attendances.day.columns.overtime'))
                    ->sortable(),
                IconColumn::make('is_manual_override')
                    ->label(__('attendances::attendances.day.columns.is_manual_override'))
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('month')
                    ->label(__('attendances::attendances.day.filters.month'))
                    ->schema([
                        Select::make('month')
                            ->label(__('attendances::attendances.day.filters.month'))
                            ->options(fn (): array => static::monthOptions())
                            ->default(fn (): string => now()->format('Y-m'))
                            ->selectablePlaceholder(false),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => static::applyMonth($query, $data['month'] ?? null)),
                SelectFilter::make('employee_id')
                    ->label(__('attendances::attendances.day.filters.employee'))
                    ->options(fn (): array => static::employeeQuery()->pluck('name', 'id')->all())
                    ->searchable(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->defaultSort('date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListAttendanceDays::route('/'),
            'create' => CreateAttendanceDay::route('/create'),
            'edit'   => EditAttendanceDay::route('/{record}/edit'),
        ];
    }

    /**
     * The month ("Y-m") the list shows; an empty or malformed value falls back to the current month so
     * the list and the CSV export always agree.
     */
    public static function normaliseMonth(mixed $month): string
    {
        return is_string($month) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) === 1
            ? $month
            : now()->format('Y-m');
    }

    /**
     * @param  Builder<AttendanceDay>  $query
     * @return Builder<AttendanceDay>
     */
    public static function applyMonth(Builder $query, mixed $month): Builder
    {
        $first = Carbon::createFromFormat('Y-m-d', static::normaliseMonth($month).'-01', 'UTC')->startOfDay();

        return $query->whereBetween('date', [$first->toDateString(), $first->copy()->endOfMonth()->toDateString()]);
    }

    /**
     * @return array<string, string>
     */
    public static function monthOptions(): array
    {
        $options = [];
        $month = now()->startOfMonth();

        for ($i = 0; $i < static::MONTH_CHOICES; $i++) {
            $options[$month->format('Y-m')] = $month->copy()->locale(app()->getLocale())->translatedFormat('F Y');
            $month = $month->copy()->subMonthNoOverflow();
        }

        return $options;
    }

    /**
     * Employees the signed-in user may pick or filter by, archived ones included (their rows still list).
     *
     * @return Builder<Employee>
     */
    public static function employeeQuery(): Builder
    {
        return AttendanceScope::applyTo(Employee::query()->withTrashed()->orderBy('name'), Auth::user(), 'id');
    }

    protected static function localTime(AttendanceDay $record, ?Carbon $moment): string
    {
        $zone = $record->employee?->time_zone ?: config('app.timezone');

        return $moment?->copy()->setTimezone($zone)->format('H:i') ?? '-';
    }
}
