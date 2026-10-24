<?php

namespace App\Filament\Widgets\Projects;

use App\Filament\Concerns\InheritsWidgetShieldPermission;
use Filament\Tables\Table;
use Webkul\Project\Filament\Widgets\TopProjectsWidget as BaseTopProjectsWidget;

class TopProjectsWidget extends BaseTopProjectsWidget
{
    use InheritsWidgetShieldPermission;

    public function table(Table $table): Table
    {
        return parent::table($table)
            ->emptyStateHeading(__('projects-extensions::empty.timesheets.heading'))
            ->emptyStateDescription(__('projects-extensions::empty.timesheets.description'))
            ->emptyStateIcon('heroicon-o-clock');
    }
}
