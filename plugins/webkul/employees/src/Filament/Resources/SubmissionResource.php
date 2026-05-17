<?php

namespace Webkul\Employee\Filament\Resources;

use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Webkul\Employee\Filament\Resources\SubmissionResource\Pages\ListSubmissions;
use Webkul\Employee\Filament\Resources\SubmissionResource\Pages\ViewSubmission;
use Webkul\Employee\Models\EmployeeSubmission;

class SubmissionResource extends Resource
{
    protected static ?string $model = EmployeeSubmission::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $recordTitleAttribute = 'ticket_number';

    public static function getNavigationLabel(): string
    {
        return __('employees::filament/resources/submission.navigation.title');
    }

    public static function getNavigationGroup(): string
    {
        return __('employees::filament/resources/submission.navigation.group');
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) EmployeeSubmission::open()->count();
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['ticket_number', 'subject', 'body', 'submitter_name'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('employees::filament/resources/submission.global-search.type')     => __('employees::filament/resources/submission.types.'.$record->type),
            __('employees::filament/resources/submission.global-search.status')   => __('employees::filament/resources/submission.statuses.'.$record->status),
            __('employees::filament/resources/submission.global-search.employee') => $record->submitter_name ?? '—',
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ticket_number')
                    ->label(__('employees::filament/resources/submission.table.columns.ticket-number'))
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->copyable(),
                TextColumn::make('type')
                    ->label(__('employees::filament/resources/submission.table.columns.type'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'complaint'  => 'danger',
                        'suggestion' => 'info',
                        'inquiry'    => 'warning',
                        'feedback'   => 'teal',
                        default      => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => __('employees::filament/resources/submission.types.'.$state)),
                TextColumn::make('subject')
                    ->label(__('employees::filament/resources/submission.table.columns.subject'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('submitter_name')
                    ->label(__('employees::filament/resources/submission.table.columns.submitter'))
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('department.name')
                    ->label(__('employees::filament/resources/submission.table.columns.department'))
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('employees::filament/resources/submission.table.columns.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'open'         => 'gray',
                        'under_review' => 'warning',
                        'resolved'     => 'success',
                        'closed'       => 'gray',
                        default        => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => __('employees::filament/resources/submission.statuses.'.$state)),
                TextColumn::make('priority')
                    ->label(__('employees::filament/resources/submission.table.columns.priority'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'low'    => 'gray',
                        'medium' => 'warning',
                        'high'   => 'danger',
                        default  => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => __('employees::filament/resources/submission.priorities.'.$state)),
                TextColumn::make('replies_count')
                    ->label(__('employees::filament/resources/submission.table.columns.replies'))
                    ->counts('replies')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->iconPosition('before')
                    ->numeric(),
                TextColumn::make('created_at')
                    ->label(__('employees::filament/resources/submission.table.columns.created-at'))
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('employees::filament/resources/submission.table.filters.type'))
                    ->options([
                        'complaint'  => __('employees::filament/resources/submission.types.complaint'),
                        'suggestion' => __('employees::filament/resources/submission.types.suggestion'),
                        'inquiry'    => __('employees::filament/resources/submission.types.inquiry'),
                        'feedback'   => __('employees::filament/resources/submission.types.feedback'),
                    ]),
                SelectFilter::make('status')
                    ->label(__('employees::filament/resources/submission.table.filters.status'))
                    ->options([
                        'open'         => __('employees::filament/resources/submission.statuses.open'),
                        'under_review' => __('employees::filament/resources/submission.statuses.under_review'),
                        'resolved'     => __('employees::filament/resources/submission.statuses.resolved'),
                        'closed'       => __('employees::filament/resources/submission.statuses.closed'),
                    ]),
                SelectFilter::make('priority')
                    ->label(__('employees::filament/resources/submission.table.filters.priority'))
                    ->options([
                        'low'    => __('employees::filament/resources/submission.priorities.low'),
                        'medium' => __('employees::filament/resources/submission.priorities.medium'),
                        'high'   => __('employees::filament/resources/submission.priorities.high'),
                    ]),
                SelectFilter::make('department_id')
                    ->label(__('employees::filament/resources/submission.table.filters.department'))
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('no_replies')
                    ->label(__('employees::filament/resources/submission.table.filters.no-replies'))
                    ->query(fn (Builder $query): Builder => $query->whereDoesntHave('replies')),
            ], layout: FiltersLayout::Modal)
            ->filtersFormColumns(2)
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->form(fn (Form $form) => $form->schema([
                        Select::make('status')
                            ->label(__('employees::filament/resources/submission.form.fields.status'))
                            ->options([
                                'open'         => __('employees::filament/resources/submission.statuses.open'),
                                'under_review' => __('employees::filament/resources/submission.statuses.under_review'),
                                'resolved'     => __('employees::filament/resources/submission.statuses.resolved'),
                                'closed'       => __('employees::filament/resources/submission.statuses.closed'),
                            ])
                            ->required(),
                        Select::make('priority')
                            ->label(__('employees::filament/resources/submission.form.fields.priority'))
                            ->options([
                                'low'    => __('employees::filament/resources/submission.priorities.low'),
                                'medium' => __('employees::filament/resources/submission.priorities.medium'),
                                'high'   => __('employees::filament/resources/submission.priorities.high'),
                            ])
                            ->required(),
                    ])),
                DeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('employees::filament/resources/submission.table.actions.delete.notification.title'))
                            ->body(__('employees::filament/resources/submission.table.actions.delete.notification.body')),
                    ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('markUnderReview')
                        ->label(__('employees::filament/resources/submission.table.bulk-actions.mark-under-review'))
                        ->icon('heroicon-o-eye')
                        ->action(function ($records) {
                            $records->each(fn ($r) => $r->update(['status' => 'under_review']));
                        }),
                    BulkAction::make('markResolved')
                        ->label(__('employees::filament/resources/submission.table.bulk-actions.mark-resolved'))
                        ->icon('heroicon-o-check-circle')
                        ->action(function ($records) {
                            $records->each(fn ($r) => $r->update(['status' => 'resolved', 'resolved_at' => now()]));
                        }),
                    BulkAction::make('markClosed')
                        ->label(__('employees::filament/resources/submission.table.bulk-actions.mark-closed'))
                        ->icon('heroicon-o-x-circle')
                        ->action(function ($records) {
                            $records->each(fn ($r) => $r->update(['status' => 'closed', 'closed_at' => now()]));
                        }),
                    DeleteBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('employees::filament/resources/submission.table.bulk-actions.delete.notification.title'))
                                ->body(__('employees::filament/resources/submission.table.bulk-actions.delete.notification.body')),
                        ),
                ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('employees::filament/resources/submission.infolist.sections.details.title'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Group::make([
                                    TextEntry::make('ticket_number')
                                        ->label(__('employees::filament/resources/submission.infolist.sections.details.entries.ticket-number'))
                                        ->fontFamily('mono')
                                        ->size('large')
                                        ->weight('bold'),
                                    TextEntry::make('type')
                                        ->label(__('employees::filament/resources/submission.infolist.sections.details.entries.type'))
                                        ->badge()
                                        ->color(fn (string $state): string => match ($state) {
                                            'complaint'  => 'danger',
                                            'suggestion' => 'info',
                                            'inquiry'    => 'warning',
                                            'feedback'   => 'teal',
                                            default      => 'gray',
                                        })
                                        ->formatStateUsing(fn (string $state): string => __('employees::filament/resources/submission.types.'.$state)),
                                    TextEntry::make('priority')
                                        ->label(__('employees::filament/resources/submission.infolist.sections.details.entries.priority'))
                                        ->badge()
                                        ->color(fn (string $state): string => match ($state) {
                                            'low'    => 'gray',
                                            'medium' => 'warning',
                                            'high'   => 'danger',
                                            default  => 'gray',
                                        })
                                        ->formatStateUsing(fn (string $state): string => __('employees::filament/resources/submission.priorities.'.$state)),
                                    TextEntry::make('subject')
                                        ->label(__('employees::filament/resources/submission.infolist.sections.details.entries.subject'))
                                        ->weight('bold'),
                                    TextEntry::make('body')
                                        ->label(__('employees::filament/resources/submission.infolist.sections.details.entries.body'))
                                        ->columnSpanFull(),
                                ]),
                                Group::make([
                                    TextEntry::make('submitter_name')
                                        ->label(__('employees::filament/resources/submission.infolist.sections.details.entries.submitter'))
                                        ->placeholder('—'),
                                    TextEntry::make('department.name')
                                        ->label(__('employees::filament/resources/submission.infolist.sections.details.entries.department'))
                                        ->placeholder('—'),
                                    TextEntry::make('created_at')
                                        ->label(__('employees::filament/resources/submission.infolist.sections.details.entries.created-at'))
                                        ->dateTime(),
                                    TextEntry::make('status')
                                        ->label(__('employees::filament/resources/submission.infolist.sections.details.entries.status'))
                                        ->badge()
                                        ->color(fn (string $state): string => match ($state) {
                                            'open'         => 'gray',
                                            'under_review' => 'warning',
                                            'resolved'     => 'success',
                                            'closed'       => 'gray',
                                            default        => 'gray',
                                        })
                                        ->formatStateUsing(fn (string $state): string => __('employees::filament/resources/submission.statuses.'.$state)),
                                ]),
                            ]),
                    ]),
            ]);
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ViewSubmission::class,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubmissions::route('/'),
            'view'  => ViewSubmission::route('/{record}'),
        ];
    }
}
