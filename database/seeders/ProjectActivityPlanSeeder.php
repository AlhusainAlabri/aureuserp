<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Webkul\Security\Models\User;
use Webkul\Support\Models\ActivityPlan;
use Webkul\Support\Models\ActivityPlanTemplate;
use Webkul\Support\Models\ActivityType;

class ProjectActivityPlanSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('activity_plans')) {
            return;
        }

        $planName = __('projects-extensions::activity_plan.name');

        if (ActivityPlan::query()->where('plugin', 'projects')->where('name', $planName)->exists()) {
            return;
        }

        $user = User::query()->first();
        $activityType = ActivityType::query()->where('name', 'To-Do')->first();

        if (! $activityType) {
            return;
        }

        $plan = ActivityPlan::query()->create([
            'name'       => $planName,
            'plugin'     => 'projects',
            'is_active'  => true,
            'creator_id' => $user?->id,
            'company_id' => $user?->default_company_id,
        ]);

        ActivityPlanTemplate::query()->create([
            'plan_id'          => $plan->id,
            'activity_type_id' => $activityType->id,
            'summary'          => __('projects-extensions::activity_plan.template_summary'),
            'delay_count'      => 7,
            'delay_unit'       => 'days',
            'delay_from'       => 'previous_activity',
            'responsible_type' => 'on_demand',
            'creator_id'       => $user?->id,
        ]);
    }
}
