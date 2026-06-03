<?php

namespace App\Filament\Extensions;

use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class AccountResourceExtensions
{
    public static function isAvailable(): bool
    {
        return Schema::hasTable('projects_projects')
            && Schema::hasTable('accounts_account_moves')
            && Schema::hasColumn('accounts_account_moves', 'project_id');
    }

    /** @return array<int, mixed> */
    public static function projectSelectField(): array
    {
        if (! self::isAvailable()) {
            return [];
        }

        return [
            Select::make('project_id')
                ->label(__('projects-extensions::fields.project'))
                ->relationship(
                    'project',
                    'name',
                    fn (Builder $query) => $query->orderBy('name'),
                )
                ->searchable()
                ->preload()
                ->nullable(),
        ];
    }
}
