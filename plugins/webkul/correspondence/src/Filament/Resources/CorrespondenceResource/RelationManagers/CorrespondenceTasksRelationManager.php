<?php

namespace Webkul\Correspondence\Filament\Resources\CorrespondenceResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Webkul\Correspondence\Models\Correspondence;
use Webkul\Correspondence\Services\CorrespondenceTaskService;
use Webkul\Project\Enums\TaskState;
use Webkul\Project\Filament\Resources\TaskResource;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\Task;
use Webkul\Security\Models\User;

class CorrespondenceTasksRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';

    public static function getTitle($ownerRecord = null, ?string $pageClass = null): string
    {
        return __('correspondence::correspondence.tasks.navigation');
    }

    public static function canViewForRecord($ownerRecord, string $pageClass): bool
    {
        return CorrespondenceTaskService::isAvailable();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label(__('correspondence::correspondence.tasks.title'))
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->label(__('correspondence::correspondence.body'))
                    ->columnSpanFull(),
                Select::make('assignee_id')
                    ->label(__('correspondence::correspondence.tasks.assignee'))
                    ->options(fn (): array => User::query()->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload()
                    ->required(),
                DateTimePicker::make('deadline')
                    ->label(__('correspondence::correspondence.tasks.deadline'))
                    ->native(false),
                Select::make('project_id')
                    ->label(__('correspondence::correspondence.project'))
                    ->options(fn (): array => Project::query()->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload()
                    ->default(fn (): ?int => $this->getOwnerRecord()->project_id),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('correspondence::correspondence.tasks.title'))
                    ->searchable(),
                TextColumn::make('users.name')
                    ->label(__('correspondence::correspondence.tasks.assignee'))
                    ->listWithLineBreaks()
                    ->limitList(2),
                TextColumn::make('deadline')
                    ->label(__('correspondence::correspondence.tasks.deadline'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('state')
                    ->label(__('correspondence::correspondence.status.label'))
                    ->formatStateUsing(fn (?TaskState $state): string => $state?->getLabel() ?? '-')
                    ->badge(),
            ])
            ->headerActions([
                Action::make('createTask')
                    ->label(__('correspondence::correspondence.tasks.create'))
                    ->icon('heroicon-o-plus-circle')
                    ->schema($this->form(Schema::make())->getComponents())
                    ->action(function (array $data): void {
                        /** @var Correspondence $correspondence */
                        $correspondence = $this->getOwnerRecord();

                        $task = CorrespondenceTaskService::createFromCorrespondence($correspondence, $data);

                        if (! $task) {
                            Notification::make()
                                ->danger()
                                ->title(__('correspondence::correspondence.exceptions.task_create_failed'))
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->success()
                            ->title(__('correspondence::correspondence.tasks.created'))
                            ->send();
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (Task $record): string => TaskResource::getUrl('view', ['record' => $record])),
            ])
            ->emptyStateHeading(__('correspondence::correspondence.tasks.empty'));
    }
}
