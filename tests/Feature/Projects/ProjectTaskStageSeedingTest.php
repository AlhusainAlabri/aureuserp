<?php

use App\Services\Projects\TaskStageHelper;
use Illuminate\Support\Facades\Schema;
use Webkul\Project\Models\Project;
use Webkul\Security\Models\User;

it('seeds default kanban task stages when a project is created', function (): void {
    if (! Schema::hasTable('projects_task_stages')) {
        test()->markTestSkipped('Projects task stages table is unavailable.');
    }

    app()->setLocale('ar');

    $user = User::factory()->create();

    $project = Project::factory()->create([
        'user_id'    => $user->id,
        'creator_id' => $user->id,
    ]);

    expect($project->taskStages()->count())->toBe(4)
        ->and($project->taskStages()->orderBy('sort')->pluck('name')->all())->toBe([
            'جديدة',
            'قيد التنفيذ',
            'مراجعة',
            'مكتمل',
        ]);
});

it('does not duplicate default task stages when seed helper runs twice', function (): void {
    if (! Schema::hasTable('projects_task_stages')) {
        test()->markTestSkipped('Projects task stages table is unavailable.');
    }

    $user = User::factory()->create();

    $project = Project::factory()->create([
        'user_id'    => $user->id,
        'creator_id' => $user->id,
    ]);

    TaskStageHelper::seedDefaultsForProject($project);

    expect($project->taskStages()->count())->toBe(4);
});
