<?php

namespace App\Console\Commands;

use App\Enums\Projects\TaskPriorityLevel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Webkul\Project\Enums\TaskState;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\Task;
use Webkul\Project\Models\TaskStage;
use Webkul\Security\Models\User;

class SeedKanbanDemoData extends Command
{
    protected $signature = 'projects:seed-kanban-demo {--force : Re-create demo project, stages, and tasks}';

    protected $description = 'Seed task stages and sample tasks for Kanban testing';

    private const DEMO_PROJECT_NAME = 'مشروع تجريبي — كانبان';

    public function handle(): int
    {
        if (! Schema::hasTable('projects_tasks') || ! Schema::hasTable('projects_task_stages')) {
            $this->error('Projects tables not found. Install the projects plugin first.');

            return self::FAILURE;
        }

        $admin = User::query()->where('email', 'nodhumtech@gmail.com')->first()
            ?? User::query()->first();

        if (! $admin) {
            $this->error('No user found to assign as creator.');

            return self::FAILURE;
        }

        $companyId = $admin->default_company_id;

        $existingProject = Project::query()
            ->where('name', self::DEMO_PROJECT_NAME)
            ->first();

        if ($existingProject && ! $this->option('force')) {
            $this->info('Kanban demo data already exists. Use --force to recreate.');

            return self::SUCCESS;
        }

        if ($existingProject && $this->option('force')) {
            Task::query()->where('project_id', $existingProject->id)->forceDelete();
            TaskStage::query()->where('project_id', $existingProject->id)->forceDelete();
            $existingProject->forceDelete();
        }

        $project = Project::query()->create([
            'name'       => self::DEMO_PROJECT_NAME,
            'user_id'    => $admin->id,
            'company_id' => $companyId,
            'creator_id' => $admin->id,
        ]);

        $stageNames = [
            'جديدة',
            'قيد التنفيذ',
            'مراجعة',
            'معلّقة',
        ];

        $stages = [];

        foreach ($stageNames as $index => $name) {
            $stages[] = TaskStage::query()->create([
                'name'       => $name,
                'sort'       => $index + 1,
                'is_active'  => true,
                'project_id' => $project->id,
                'company_id' => $companyId,
                'user_id'    => $admin->id,
                'creator_id' => $admin->id,
            ]);
        }

        $demoTasks = [
            [$stages[0]->id, 'إعداد تقرير الربع الثاني', TaskState::APPROVED, TaskPriorityLevel::High, 3],
            [$stages[0]->id, 'مراجعة سياسة الإجازات', TaskState::APPROVED, TaskPriorityLevel::Medium, 5],
            [$stages[1]->id, 'تحديث سجل المخزون', TaskState::IN_PROGRESS, TaskPriorityLevel::Urgent, 1],
            [$stages[1]->id, 'متابعة طلبات الشراء المفتوحة', TaskState::IN_PROGRESS, TaskPriorityLevel::Medium, 4],
            [$stages[1]->id, 'تنسيق اجتماع اللجنة', TaskState::IN_PROGRESS, TaskPriorityLevel::Low, 7],
            [$stages[2]->id, 'اعتماد محضر الاجتماع', TaskState::CHANGE_REQUESTED, TaskPriorityLevel::High, 2],
            [$stages[2]->id, 'مراجعة عقود الموردين', TaskState::CHANGE_REQUESTED, TaskPriorityLevel::Medium, 6],
            [$stages[3]->id, 'أرشفة المراسلات القديمة', TaskState::CHANGE_REQUESTED, TaskPriorityLevel::Low, 10],
        ];

        foreach ($demoTasks as $sort => [$stageId, $title, $state, $priority, $dueInDays]) {
            $task = new Task;
            $attributes = [
                'title'      => $title,
                'state'      => $state,
                'stage_id'   => $stageId,
                'project_id' => $project->id,
                'company_id' => $companyId,
                'creator_id' => $admin->id,
                'sort'       => $sort + 1,
                'deadline'   => now()->addDays($dueInDays),
                'priority'   => $priority === TaskPriorityLevel::High,
            ];

            if (Schema::hasColumn('projects_tasks', 'priority_level')) {
                $attributes['priority_level'] = $priority->value;
            }

            if (Schema::hasColumn('projects_tasks', 'owner_id')) {
                $attributes['owner_id'] = $admin->id;
            }

            $task->forceFill($attributes);
            $task->save();

            if (Schema::hasTable('projects_task_users')) {
                $task->users()->sync([$admin->id]);
            }
        }

        $this->info(sprintf(
            'Created project "%s" with %d stages and %d tasks.',
            self::DEMO_PROJECT_NAME,
            count($stages),
            count($demoTasks),
        ));
        $this->line('Open: /admin/projects/task-hub/kanban?lang=ar');

        return self::SUCCESS;
    }
}
