<?php

namespace App\Filament\Employees\Resources\EmployeeResource\Pages;

use Filament\Actions\Action;
use Webkul\Employee\Filament\Clusters\Configurations;
use Webkul\Employee\Filament\Clusters\Reportings\Resources\EmployeeSkillResource;
use Webkul\Employee\Filament\Resources\EmployeeResource\Pages\ListEmployees as BaseListEmployees;

class ListEmployees extends BaseListEmployees
{
    protected function getHeaderActions(): array
    {
        return [
            Action::make('employeeConfigurations')
                ->label(__('employees::filament/clusters/configurations.navigation.title'))
                ->icon('heroicon-o-cog-6-tooth')
                ->color('gray')
                ->url(fn (): string => Configurations::getUrl())
                ->visible(fn (): bool => Configurations::canAccess()),
            Action::make('employeeSkillsReport')
                ->label(__('employees::filament/clusters/reportings/resources/employee-skill.navigation.title'))
                ->icon('heroicon-o-academic-cap')
                ->color('gray')
                ->url(fn (): string => EmployeeSkillResource::getUrl())
                ->visible(fn (): bool => EmployeeSkillResource::canAccess()),
            ...parent::getHeaderActions(),
        ];
    }
}
