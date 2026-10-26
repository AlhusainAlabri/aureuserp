<?php

namespace Webkul\Attendance\Filament\Resources;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Webkul\Attendance\Enums\OvertimeDayType;
use Webkul\Attendance\Enums\OvertimeStatus;
use Webkul\Attendance\Exceptions\AttendanceException;
use Webkul\Attendance\Filament\Concerns\AppendsLocaleToResourceUrls;
use Webkul\Attendance\Filament\Resources\OvertimeRequestResource\Pages\ListOvertimeRequests;
use Webkul\Attendance\Models\OvertimeRequest;
use Webkul\Attendance\Services\AttendanceDayCsv;
use Webkul\Attendance\Services\AttendanceScope;
use Webkul\Attendance\Services\OvertimeDecision;

class OvertimeRequestResource extends Resource
{
    use AppendsLocaleToResourceUrls;

    protected static ?string $model = OvertimeRequest::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static ?string $slug = 'attendances/overtime-requests';

    protected static ?int $navigationSort = 3;

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
        return __('attendances::attendances.models.overtime_request');
    }

    public static function getPluralModelLabel(): string
    {
        return __('attendances::attendances.models_plural.overtime_request');
    }

    /**
     * Requests are generated from attendance days, never typed in.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Only the requests of employees the signed-in user may see (HR: everyone; others: self and team).
     *
     * @return Builder<OvertimeRequest>
     */
    public static function getEloquentQuery(): Builder
    {
        return AttendanceScope::applyTo(
            parent::getEloquentQuery()->with(['employee', 'decider']),
            Auth::user(),
        );
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.name')
                    ->label(__('attendances::attendances.overtime.columns.employee'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('date')
                    ->label(__('attendances::attendances.overtime.columns.date'))
                    ->formatStateUsing(fn (OvertimeRequest $record): string => $record->date->translatedFormat('d M Y'))
                    ->sortable(),
                TextColumn::make('minutes')
                    ->label(__('attendances::attendances.overtime.columns.hours'))
                    ->formatStateUsing(fn (?int $state): string => AttendanceDayCsv::formatMinutes((int) $state))
                    ->sortable(),
                TextColumn::make('day_type')
                    ->label(__('attendances::attendances.overtime.columns.day_type'))
                    ->badge(),
                TextColumn::make('multiplier')
                    ->label(__('attendances::attendances.overtime.columns.multiplier'))
                    ->formatStateUsing(fn (mixed $state): string => '×'.rtrim(rtrim(number_format((float) $state, 2, '.', ''), '0'), '.'))
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('attendances::attendances.overtime.columns.status'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('decider.name')
                    ->label(__('attendances::attendances.overtime.columns.decided_by'))
                    ->placeholder('-'),
                TextColumn::make('decided_at')
                    ->label(__('attendances::attendances.overtime.columns.decided_at'))
                    ->dateTime()
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('note')
                    ->label(__('attendances::attendances.overtime.columns.note'))
                    ->placeholder('-')
                    ->limit(60)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('day_type')
                    ->label(__('attendances::attendances.overtime.columns.day_type'))
                    ->options(OvertimeDayType::class),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label(__('attendances::attendances.overtime.actions.approve'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->authorize('approve')
                    ->visible(fn (OvertimeRequest $record): bool => $record->status === OvertimeStatus::Pending)
                    ->modalHeading(__('attendances::attendances.overtime.actions.approve_heading'))
                    ->schema([static::noteField()])
                    ->action(fn (OvertimeRequest $record, array $data) => static::decide($record, true, $data['note'] ?? null)),
                Action::make('reject')
                    ->label(__('attendances::attendances.overtime.actions.reject'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->authorize('reject')
                    ->visible(fn (OvertimeRequest $record): bool => $record->status === OvertimeStatus::Pending)
                    ->modalHeading(__('attendances::attendances.overtime.actions.reject_heading'))
                    ->schema([static::noteField()])
                    ->action(fn (OvertimeRequest $record, array $data) => static::decide($record, false, $data['note'] ?? null)),
            ])
            ->toolbarActions([
                BulkAction::make('approve')
                    ->label(__('attendances::attendances.overtime.actions.approve_selected'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->authorizeIndividualRecords('approve')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(fn (Collection $records) => static::approveMany($records)),
            ])
            ->defaultSort('date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOvertimeRequests::route('/'),
        ];
    }

    protected static function noteField(): Textarea
    {
        return Textarea::make('note')
            ->label(__('attendances::attendances.overtime.fields.note'))
            ->helperText(__('attendances::attendances.overtime.fields.note_hint'))
            ->rows(3)
            ->maxLength(1000);
    }

    protected static function decide(OvertimeRequest $record, bool $approve, ?string $note): void
    {
        $decision = app(OvertimeDecision::class);

        try {
            $approve
                ? $decision->approve($record, Auth::user(), $note)
                : $decision->reject($record, Auth::user(), $note);
        } catch (AttendanceException $exception) {
            Notification::make()->danger()->title($exception->getMessage())->send();

            return;
        }

        Notification::make()
            ->success()
            ->title(__($approve
                ? 'attendances::attendances.overtime.notifications.approved'
                : 'attendances::attendances.overtime.notifications.rejected'))
            ->send();
    }

    /**
     * Approves what may be approved and reports how many requests were left alone (decided meanwhile,
     * or the user's own).
     *
     * @param  Collection<int, OvertimeRequest>  $records
     */
    protected static function approveMany(Collection $records): void
    {
        $decision = app(OvertimeDecision::class);
        $approved = 0;
        $skipped = 0;

        foreach ($records as $record) {
            try {
                $decision->approve($record, Auth::user());
                $approved++;
            } catch (AttendanceException) {
                $skipped++;
            }
        }

        $notification = Notification::make()
            ->title(__('attendances::attendances.overtime.notifications.bulk_approved', ['count' => $approved]));

        if ($skipped > 0) {
            $notification->warning()->body(__('attendances::attendances.overtime.notifications.bulk_skipped', ['count' => $skipped]));
        } else {
            $notification->success();
        }

        $notification->send();
    }
}
