<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Projects\Pages\TaskKanban;
use App\Services\Projects\TaskStageHelper;
use App\Services\Projects\TaskStatePresenter;
use App\Support\FilamentUrl;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Webkul\Project\Filament\Resources\ProjectResource\Pages\ManageTasks as BaseManageTasks;
use Webkul\Project\Filament\Resources\TaskResource;

class ManageTasks extends BaseManageTasks
{
    public function form(Schema $schema): Schema
    {
        return TaskResource::form($schema);
    }

    public static function getRelationshipTitle(): string
    {
        return __('projects::filament/resources/project/pages/manage-tasks.title');
    }

    public function getTitle(): string|Htmlable
    {
        return __('projects-extensions::pages.manage_tasks.title', [
            'project' => $this->getRecordTitle(),
        ]);
    }

    public function getBreadcrumb(): string
    {
        return __('projects::filament/resources/project/pages/manage-tasks.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kanban')
                ->label(__('tasks.hub.view_kanban'))
                ->icon('heroicon-o-view-columns')
                ->url(fn (): string => FilamentUrl::appendLocaleToUrl(
                    TaskKanban::getUrl(['filterProjectId' => $this->getRecord()->id]),
                ))
                ->color('gray'),
        ];
    }

    public function table(Table $table): Table
    {
        $project = $this->getRecord();

        return parent::table($table)
            ->emptyStateHeading(__('projects-extensions::empty.tasks.heading'))
            ->emptyStateDescription(__('projects-extensions::empty.tasks.description'))
            ->headerActions([
                CreateAction::make()
                    ->label(__('projects-extensions::actions.add_task'))
                    ->icon('heroicon-o-plus-circle')
                    ->modalHeading(__('projects-extensions::actions.add_task'))
                    ->fillForm(function () use ($project): array {
                        $stage = TaskStageHelper::resolveDefaultForProject($project);

                        return [
                            'project_id' => $project->id,
                            'partner_id' => $project->partner_id,
                            'stage_id'   => $stage?->id,
                            'state'      => TaskStatePresenter::defaultState(),
                        ];
                    })
                    ->mutateDataUsing(function (array $data) use ($project): array {
                        $stage = TaskStageHelper::resolveDefaultForProject($project);

                        $data['project_id'] ??= $project->id;
                        $data['partner_id'] ??= $project->partner_id;
                        $data['stage_id'] ??= $stage?->id;
                        $data['state'] ??= TaskStatePresenter::defaultState();

                        return $data;
                    })
                    ->modalWidth('6xl')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('projects-extensions::notifications.task_created.title'))
                            ->body(__('projects-extensions::notifications.task_created.body')),
                    ),
            ]);
    }
}
