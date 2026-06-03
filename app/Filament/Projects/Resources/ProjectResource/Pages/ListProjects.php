<?php

namespace App\Filament\Projects\Resources\ProjectResource\Pages;

use Filament\Actions\Action;
use Webkul\Project\Filament\Clusters\Configurations\Resources\TaskStageResource;
use Webkul\Project\Filament\Resources\ProjectResource\Pages\ListProjects as BaseListProjects;

class ListProjects extends BaseListProjects
{
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
