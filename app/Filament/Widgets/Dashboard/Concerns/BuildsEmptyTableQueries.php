<?php

namespace App\Filament\Widgets\Dashboard\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

trait BuildsEmptyTableQueries
{
    /**
     * @param  class-string<Model>  $modelClass
     * @param  array<string, string>  $columns
     */
    protected function emptyTableQuery(string $modelClass, array $columns): Builder
    {
        /** @var Model $model */
        $model = new $modelClass;

        $selects = collect($columns)
            ->map(fn (string $expression, string $column) => DB::raw("{$expression} as {$column}"))
            ->values()
            ->all();

        return $modelClass::withoutGlobalScopes()->fromSub(
            DB::query()->select($selects)->whereRaw('0 = 1'),
            $model->getTable(),
        );
    }
}
