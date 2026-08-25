<?php

namespace Webkul\Support\Filament\Resources;

use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Webkul\Support\Filament\Clusters\Settings;
use Webkul\Support\Filament\Resources\EmailLogResource\Pages\ListEmailLogs;
use Webkul\Support\Filament\Resources\EmailLogResource\Pages\ViewEmailLog;
use Webkul\Support\Models\EmailLog;

class EmailLogResource extends Resource
{
    protected static ?string $model = EmailLog::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-envelope-open';

    protected static ?string $cluster = Settings::class;

    protected static ?int $navigationSort = 90;

    protected static ?string $slug = 'email-logs';

    public static function getNavigationGroup(): string
    {
        return __('support::filament/clusters/settings/pages/settings.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('support::filament/resources/email-log.navigation.title');
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

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('recipient_email')
                    ->label(__('support::filament/resources/email-log.table.columns.recipient_email'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('recipient_name')
                    ->label(__('support::filament/resources/email-log.table.columns.recipient_name'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('subject')
                    ->label(__('support::filament/resources/email-log.table.columns.subject'))
                    ->searchable()
                    ->limit(60),
                TextColumn::make('status')
                    ->label(__('support::filament/resources/email-log.table.columns.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'queued'  => 'info',
                        'sent'    => 'success',
                        'failed'  => 'danger',
                        default   => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('mail_class')
                    ->label(__('support::filament/resources/email-log.table.columns.mail_class'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(40),
                TextColumn::make('sender.name')
                    ->label(__('support::filament/resources/email-log.table.columns.sender'))
                    ->toggleable(),
                TextColumn::make('sent_at')
                    ->label(__('support::filament/resources/email-log.table.columns.sent_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('support::filament/resources/email-log.table.columns.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('support::filament/resources/email-log.table.filters.status'))
                    ->options([
                        'queued' => __('support::filament/resources/email-log.status.queued'),
                        'sent'   => __('support::filament/resources/email-log.status.sent'),
                        'failed' => __('support::filament/resources/email-log.status.failed'),
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('support::filament/resources/email-log.infolist.sections.details'))
                    ->schema([
                        TextEntry::make('recipient_email')
                            ->label(__('support::filament/resources/email-log.table.columns.recipient_email')),
                        TextEntry::make('recipient_name')
                            ->label(__('support::filament/resources/email-log.table.columns.recipient_name')),
                        TextEntry::make('subject')
                            ->label(__('support::filament/resources/email-log.table.columns.subject'))
                            ->columnSpanFull(),
                        TextEntry::make('status')
                            ->label(__('support::filament/resources/email-log.table.columns.status'))
                            ->badge(),
                        TextEntry::make('mail_class')
                            ->label(__('support::filament/resources/email-log.table.columns.mail_class')),
                        TextEntry::make('sender.name')
                            ->label(__('support::filament/resources/email-log.table.columns.sender')),
                        TextEntry::make('emailTemplate.name')
                            ->label(__('support::filament/resources/email-log.table.columns.template')),
                        TextEntry::make('sent_at')
                            ->label(__('support::filament/resources/email-log.table.columns.sent_at'))
                            ->dateTime(),
                        TextEntry::make('error_message')
                            ->label(__('support::filament/resources/email-log.table.columns.error_message'))
                            ->columnSpanFull()
                            ->visible(fn (?EmailLog $record): bool => filled($record?->error_message)),
                        TextEntry::make('variables')
                            ->label(__('support::filament/resources/email-log.table.columns.variables'))
                            ->formatStateUsing(fn (?array $state): string => $state ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '')
                            ->columnSpanFull()
                            ->visible(fn (?EmailLog $record): bool => filled($record?->variables)),
                    ])
                    ->columns(2),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmailLogs::route('/'),
            'view'  => ViewEmailLog::route('/{record}'),
        ];
    }
}
