<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages\ListAuditLogs;
use App\Models\Audit\AuditEntry;
use BackedEnum;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use Webkul\Security\Models\User;

/**
 * Read-only list of every recorded change: who did what to which record, and when.
 */
class AuditLogResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = AuditEntry::class;

    protected static ?string $slug = 'audit-logs';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 3;

    protected static bool $isGloballySearchable = false;

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
        ];
    }

    public static function getNavigationGroup(): string
    {
        return __('audit.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('audit.navigation.label');
    }

    public static function getModelLabel(): string
    {
        return __('audit.title');
    }

    public static function getPluralModelLabel(): string
    {
        return __('audit.title');
    }

    public static function canAccess(): bool
    {
        return SchemaFacade::hasTable('chatter_messages') && parent::canAccess();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    /**
     * Only the entries of the companies the signed-in user may work in.
     */
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        $companyIds = $user instanceof User
            ? $user->allowedCompanies()->pluck('companies.id')->all()
            : [];

        return parent::getEloquentQuery()
            ->whereIn('company_id', $companyIds)
            ->with(['causer', 'messageable']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('audit.table.when'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('causer_id')
                    ->label(__('audit.table.who'))
                    ->state(fn (AuditEntry $record): string => $record->causer?->name ?? __('audit.table.unknown')),
                TextColumn::make('messageable_type')
                    ->label(__('audit.table.record_type'))
                    ->state(fn (AuditEntry $record): string => $record->subjectLabel()),
                TextColumn::make('messageable_id')
                    ->label(__('audit.table.record'))
                    ->state(fn (AuditEntry $record): string => '#'.$record->messageable_id)
                    ->url(fn (AuditEntry $record): ?string => $record->recordUrl()),
                TextColumn::make('event')
                    ->label(__('audit.table.event'))
                    ->badge()
                    ->state(fn (AuditEntry $record): string => AuditEntry::eventNameFor((string) $record->event)),
                TextColumn::make('summary')
                    ->label(__('audit.table.summary'))
                    ->state(fn (AuditEntry $record): array => $record->summaryLines())
                    ->listWithLineBreaks()
                    ->limitList(3)
                    ->expandableLimitedList()
                    ->placeholder(__('audit.table.empty'))
                    ->searchable(query: fn (Builder $query, string $search): Builder => static::searchSummary($query, $search)),
            ])
            ->defaultSort(fn (Builder $query): Builder => $query->orderByDesc('created_at')->orderByDesc('id'))
            ->filters([
                SelectFilter::make('causer_id')
                    ->label(__('audit.table.who'))
                    ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable(),
                SelectFilter::make('messageable_type')
                    ->label(__('audit.table.record_type'))
                    ->options(fn (): array => static::recordTypeOptions())
                    ->searchable(),
                SelectFilter::make('event')
                    ->label(__('audit.table.event'))
                    ->options(fn (): array => static::eventOptions()),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from')->label(__('audit.table.from')),
                        DatePicker::make('until')->label(__('audit.table.until')),
                    ])
                    ->columns(2)
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $from): Builder => $query->where('created_at', '>=', Carbon::parse($from)->startOfDay()))
                        ->when($data['until'] ?? null, fn (Builder $query, string $until): Builder => $query->where('created_at', '<=', Carbon::parse($until)->endOfDay())))
                    ->indicateUsing(fn (array $data): array => array_values(array_filter([
                        ($data['from'] ?? null) ? __('audit.table.from').': '.Carbon::parse($data['from'])->toFormattedDateString() : null,
                        ($data['until'] ?? null) ? __('audit.table.until').': '.Carbon::parse($data['until'])->toFormattedDateString() : null,
                    ]))),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
        ];
    }

    /**
     * The summary is built from the stored values, so it is searched in the stored properties (as written and as
     * JSON-escaped, which is how non-Latin text may be stored) and by event name.
     */
    protected static function searchSummary(Builder $query, string $search): Builder
    {
        $escapedSearch = substr((string) json_encode($search), 1, -1);

        $patterns = collect([$search, $escapedSearch])
            ->unique()
            ->map(fn (string $term): string => '%'.addcslashes($term, '\\%_').'%')
            ->all();

        $events = collect(array_keys(trans('audit.events')))
            ->filter(fn (string $event): bool => str_contains(
                mb_strtolower(AuditEntry::eventNameFor($event)),
                mb_strtolower($search),
            ))
            ->values()
            ->all();

        return $query->where(function (Builder $query) use ($patterns, $events): void {
            foreach ($patterns as $pattern) {
                $query->orWhere('properties', 'like', $pattern);
            }

            $query->orWhereIn('event', $events);
        });
    }

    /**
     * @return array<string, string>
     */
    protected static function recordTypeOptions(): array
    {
        return AuditEntry::query()
            ->distinct()
            ->orderBy('messageable_type')
            ->pluck('messageable_type')
            ->mapWithKeys(fn (string $type): array => [$type => AuditEntry::subjectLabelFor($type)])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected static function eventOptions(): array
    {
        return collect(array_keys(trans('audit.events')))
            ->mapWithKeys(fn (string $event): array => [$event => AuditEntry::eventNameFor($event)])
            ->all();
    }
}
