<?php

namespace App\Filament\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Schema as DbSchema;
use Illuminate\Support\Facades\Storage;
use Webkul\Meetings\Filament\Resources\MeetingResource;
use Webkul\Meetings\Models\Meeting;

class EnhancedProjectMeetingsRelationManager extends RelationManager
{
    protected static string $relationship = 'meetings';

    public static function getTitle($ownerRecord = null, ?string $pageClass = null): string
    {
        return __('projects-extensions::relations.meetings');
    }

    public static function canViewForRecord($ownerRecord, string $pageClass): bool
    {
        return DbSchema::hasTable('meetings');
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('meetings::meetings.form.sections.meeting_data'))
                    ->schema([
                        TextInput::make('title')
                            ->label(__('meetings::meetings.fields.title'))
                            ->required()
                            ->maxLength(255),
                        Select::make('type')
                            ->label(__('meetings::meetings.fields.type'))
                            ->options(MeetingResource::typeOptions())
                            ->required(),
                        DateTimePicker::make('meeting_date')
                            ->label(__('meetings::meetings.fields.meeting_date'))
                            ->native(false)
                            ->seconds(false)
                            ->required(),
                        TextInput::make('location')
                            ->label(__('meetings::meetings.fields.location'))
                            ->maxLength(255),
                        Hidden::make('project_id'),
                        MeetingResource::userSelect('chair_person_id', __('meetings::meetings.fields.chair_person')),
                    ])
                    ->columns(2),
                Section::make(__('meetings::meetings.form.sections.agenda'))
                    ->schema([
                        MeetingResource::minutesRichEditor('agenda', 22),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('meeting_number')
                    ->label(__('meetings::meetings.fields.meeting_number')),
                TextColumn::make('title')
                    ->label(__('meetings::meetings.fields.title'))
                    ->wrap(),
                TextColumn::make('status')
                    ->label(__('meetings::meetings.fields.status'))
                    ->badge(),
                TextColumn::make('meeting_date')
                    ->label(__('meetings::meetings.fields.meeting_date'))
                    ->dateTime('d M Y H:i'),
                TextColumn::make('chairPerson.name')
                    ->label(__('meetings::meetings.fields.chair_person'))
                    ->placeholder('—'),
                IconColumn::make('pdf_path')
                    ->label(__('projects-extensions::columns.minutes'))
                    ->boolean()
                    ->trueIcon('heroicon-o-document-check')
                    ->falseIcon('heroicon-o-document-minus'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('projects-extensions::actions.add_meeting'))
                    ->icon('heroicon-o-plus-circle')
                    ->fillForm(fn (): array => [
                        'project_id'      => $this->getOwnerRecord()->id,
                        'chair_person_id' => auth()->id(),
                        'company_id'      => $this->getOwnerRecord()->company_id,
                    ])
                    ->mutateDataUsing(function (array $data): array {
                        $data['project_id'] = $this->getOwnerRecord()->id;

                        if (empty($data['company_id'])) {
                            $data['company_id'] = $this->getOwnerRecord()->company_id ?? auth()->user()?->default_company_id;
                        }

                        return $data;
                    })
                    ->modalWidth('6xl')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('projects-extensions::notifications.meeting_created.title'))
                            ->body(__('projects-extensions::notifications.meeting_created.body')),
                    ),
            ])
            ->emptyStateHeading(__('projects-extensions::empty.meetings.heading'))
            ->emptyStateDescription(__('projects-extensions::empty.meetings.description'))
            ->recordActions([
                ViewAction::make()
                    ->url(fn (Meeting $record): string => MeetingResource::getUrl('view', ['record' => $record])),
                Action::make('downloadMinutes')
                    ->label(__('projects-extensions::columns.download_minutes'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->visible(fn (Meeting $record): bool => filled($record->pdf_path))
                    ->url(fn (Meeting $record): string => Storage::disk('private')->temporaryUrl(
                        $record->pdf_path,
                        now()->addMinutes(60),
                    ))
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('meeting_date', 'desc');
    }
}
