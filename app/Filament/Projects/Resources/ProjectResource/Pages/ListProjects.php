<?php

namespace App\Filament\Projects\Resources\ProjectResource\Pages;

use Filament\Actions\Action;
use Filament\Tables\Table;
use Webkul\Project\Filament\Clusters\Configurations\Resources\TaskStageResource;
use Webkul\Project\Filament\Resources\ProjectResource\Pages\ListProjects as BaseListProjects;

class ListProjects extends BaseListProjects
{
    public function table(Table $table): Table
    {
        return parent::table($table)
            ->emptyStateHeading(__('projects-extensions::empty.projects.heading'))
            ->emptyStateDescription(__('projects-extensions::empty.projects.description'))
            ->emptyStateIcon('heroicon-o-folder');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('taskStages')
                ->label(__('projects::filament/clusters/configurations/resources/task-stage.navigation.title'))
                ->icon('heroicon-o-rectangle-stack')
                ->color('gray')
                ->url(fn (): string => TaskStageResource::getUrl())
                ->visible(fn (): bool => TaskStageResource::canAccess()),
            ...parent::getHeaderActions(),
        ];
    }
}
