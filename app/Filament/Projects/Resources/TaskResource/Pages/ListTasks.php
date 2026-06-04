<?php

namespace App\Filament\Projects\Resources\TaskResource\Pages;

use App\Filament\Extensions\TaskResourceExtensions;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Project\Filament\Resources\TaskResource\Pages\ListTasks as BaseListTasks;

class ListTasks extends BaseListTasks
{
    public function table(Table $table): Table
    {
        return parent::table($table)
            ->modifyQueryUsing(fn (Builder $query): Builder => TaskResourceExtensions::applyTableEagerLoads($query))
            ->emptyStateHeading(__('tasks.empty.no_records'))
            ->emptyStateDescription(__('tasks.empty.no_records_description'))
            ->emptyStateIcon('heroicon-o-clipboard-document-check');
    }
}
