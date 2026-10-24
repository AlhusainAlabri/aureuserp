<?php

namespace Webkul\Support\Filament\Resources;

use App\Filament\Concerns\TranslatesResourceLabels;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Webkul\Support\Filament\Clusters\Settings;
use Webkul\Support\Filament\Resources\EmailTemplateResource\Pages\CreateEmailTemplate;
use Webkul\Support\Filament\Resources\EmailTemplateResource\Pages\EditEmailTemplate;
use Webkul\Support\Filament\Resources\EmailTemplateResource\Pages\ListEmailTemplates;
use Webkul\Support\Filament\Resources\EmailTemplateResource\Pages\ViewEmailTemplate;
use Webkul\Support\Models\EmailTemplate;

class EmailTemplateResource extends Resource
{
    use TranslatesResourceLabels;

    protected static ?string $model = EmailTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $cluster = Settings::class;

    protected static ?int $navigationSort = 91;

    protected static ?string $slug = 'email-templates';

    public static function getNavigationGroup(): string
    {
        return __('support::filament/clusters/settings/pages/settings.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('support::filament/resources/email-template.navigation.title');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('support::filament/resources/email-template.form.sections.general'))
                    ->schema([
                        TextInput::make('code')
                            ->label(__('support::filament/resources/email-template.form.fields.code'))
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->alphaDash()
                            ->columnSpan(1),
                        TextInput::make('name')
                            ->label(__('support::filament/resources/email-template.form.fields.name'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),
                        TextInput::make('subject')
                            ->label(__('support::filament/resources/email-template.form.fields.subject'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('sender_name')
                            ->label(__('support::filament/resources/email-template.form.fields.sender_name'))
                            ->maxLength(255)
                            ->columnSpan(1),
                        TextInput::make('layout')
                            ->label(__('support::filament/resources/email-template.form.fields.layout'))
                            ->default('default')
                            ->maxLength(255)
                            ->columnSpan(1),
                        Toggle::make('is_active')
                            ->label(__('support::filament/resources/email-template.form.fields.is_active'))
                            ->default(true)
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->label(__('support::filament/resources/email-template.form.fields.description'))
                            ->rows(3)
                            ->columnSpanFull(),
                        RichEditor::make('content')
                            ->label(__('support::filament/resources/email-template.form.fields.content'))
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label(__('support::filament/resources/email-template.table.columns.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')
                    ->label(__('support::filament/resources/email-template.table.columns.code'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('subject')
                    ->label(__('support::filament/resources/email-template.table.columns.subject'))
                    ->searchable()
                    ->limit(50),
                IconColumn::make('is_active')
                    ->label(__('support::filament/resources/email-template.table.columns.is_active'))
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label(__('support::filament/resources/email-template.table.columns.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(__('support::filament/resources/email-template.table.filters.is_active')),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('support::filament/resources/email-template.notifications.updated.title'))
                                ->body(__('support::filament/resources/email-template.notifications.updated.body')),
                        ),
                    DeleteAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('support::filament/resources/email-template.notifications.deleted.title'))
                                ->body(__('support::filament/resources/email-template.notifications.deleted.body')),
                        ),
                    RestoreAction::make(),
                    ForceDeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListEmailTemplates::route('/'),
            'create' => CreateEmailTemplate::route('/create'),
            'view'   => ViewEmailTemplate::route('/{record}'),
            'edit'   => EditEmailTemplate::route('/{record}/edit'),
        ];
    }
}
