<?php

namespace App\Support\Performance;

use Illuminate\Support\Facades\Schema;

class RecommendedIndexCatalog
{
    /**
     * @return list<array{
     *     table: string,
     *     columns: list<string>,
     *     name: string,
     *     reason: string,
     *     priority: string
     * }>
     */
    public static function definitions(): array
    {
        return [
            [
                'table'    => 'employees_employee_submissions',
                'columns'  => ['status'],
                'name'     => 'employees_submissions_status_idx',
                'reason'   => 'Navigation badge and open-submission filters count by status.',
                'priority' => 'high',
            ],
            [
                'table'    => 'projects_tasks',
                'columns'  => ['project_id', 'parent_id', 'state'],
                'name'     => 'projects_tasks_completion_idx',
                'reason'   => 'Dashboard project completion uses grouped task counts per project.',
                'priority' => 'high',
            ],
            [
                'table'    => 'projects_tasks',
                'columns'  => ['state', 'deadline'],
                'name'     => 'projects_tasks_overdue_idx',
                'reason'   => 'Overdue task widgets filter by state and deadline.',
                'priority' => 'high',
            ],
            [
                'table'    => 'doc_files',
                'columns'  => ['folder_id', 'created_at'],
                'name'     => 'doc_files_folder_created_idx',
                'reason'   => 'Document manager lists files per folder ordered by created_at.',
                'priority' => 'high',
            ],
            [
                'table'    => 'correspondences',
                'columns'  => ['direction', 'received_at'],
                'name'     => 'correspondences_direction_received_idx',
                'reason'   => 'Correspondence volume chart aggregates by direction and received_at.',
                'priority' => 'medium',
            ],
            [
                'table'    => 'correspondences',
                'columns'  => ['status'],
                'name'     => 'correspondences_status_idx',
                'reason'   => 'Unread and pending correspondence widgets filter by status.',
                'priority' => 'medium',
            ],
            [
                'table'    => 'inventories_product_quantities',
                'columns'  => ['product_id', 'location_id'],
                'name'     => 'inventories_qty_product_location_idx',
                'reason'   => 'Stock counters aggregate quantity by product and location.',
                'priority' => 'medium',
            ],
            [
                'table'    => 'approvals',
                'columns'  => ['status', 'created_at'],
                'name'     => 'approvals_status_created_idx',
                'reason'   => 'Pending approval widgets count and sort recent approvals.',
                'priority' => 'medium',
            ],
        ];
    }

    /**
     * @return list<array{
     *     table: string,
     *     columns: list<string>,
     *     name: string,
     *     reason: string,
     *     priority: string,
     *     present: bool
     * }>
     */
    public static function audit(): array
    {
        return collect(static::definitions())
            ->filter(fn (array $definition): bool => Schema::hasTable($definition['table']))
            ->map(function (array $definition): array {
                $definition['present'] = static::indexExists(
                    $definition['table'],
                    $definition['name'],
                );

                return $definition;
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array{
     *     table: string,
     *     columns: list<string>,
     *     name: string,
     *     reason: string,
     *     priority: string,
     *     present: bool
     * }>
     */
    public static function missing(): array
    {
        return collect(static::audit())
            ->reject(fn (array $definition): bool => $definition['present'])
            ->values()
            ->all();
    }

    public static function indexExists(string $table, string $indexName): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        return collect(Schema::getIndexes($table))
            ->contains(fn (array $index): bool => ($index['name'] ?? null) === $indexName);
    }
}
