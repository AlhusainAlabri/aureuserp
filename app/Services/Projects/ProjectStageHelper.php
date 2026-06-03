<?php

namespace App\Services\Projects;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Webkul\Project\Models\ProjectStage;

class ProjectStageHelper
{
    /** @var array<string, list<string>> */
    protected const STAGE_ALIASES = [
        'in_progress' => ['In Progress', 'قيد التنفيذ'],
        'done'        => ['Done', 'Completed', 'مكتمل'],
        'cancelled'   => ['Cancelled', 'Canceled', 'ملغي'],
    ];

    public static function isAvailable(): bool
    {
        return Schema::hasTable('projects_project_stages');
    }

    public static function stageIdsFor(string $key): array
    {
        if (! self::isAvailable()) {
            return [];
        }

        $names = self::STAGE_ALIASES[$key] ?? [];

        if ($names === []) {
            return [];
        }

        return ProjectStage::query()
            ->whereIn('name', $names)
            ->pluck('id')
            ->all();
    }

    public static function applyStageFilter(Builder $query, string $key): Builder
    {
        $stageIds = self::stageIdsFor($key);

        if ($stageIds === []) {
            return $query;
        }

        return $query->whereIn('stage_id', $stageIds);
    }
}
