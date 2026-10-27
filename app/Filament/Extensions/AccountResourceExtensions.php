<?php

namespace App\Filament\Extensions;

use App\Support\SchemaMemo;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;

class AccountResourceExtensions
{
    public static function isAvailable(): bool
    {
        return SchemaMemo::hasTable('projects_projects')
            && SchemaMemo::hasTable('accounts_account_moves')
            && SchemaMemo::hasColumn('accounts_account_moves', 'project_id');
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
