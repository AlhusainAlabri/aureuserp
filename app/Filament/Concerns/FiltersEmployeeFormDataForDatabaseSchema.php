<?php

namespace App\Filament\Concerns;

use Illuminate\Support\Facades\Schema;
use Webkul\Employee\Models\Employee;

trait FiltersEmployeeFormDataForDatabaseSchema
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function filterEmployeeFormDataForDatabaseSchema(array $data): array
    {
        $table = (new Employee)->getTable();

        return collect($data)
            ->only((new Employee)->getFillable())
            ->filter(fn (mixed $value, string $column): bool => Schema::hasColumn($table, $column))
            ->all();
    }
}
