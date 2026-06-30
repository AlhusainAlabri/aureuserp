<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Webkul\Employee\Models\Department;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

class OmanOrgStructureSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('employees_departments')) {
            return;
        }

        if (Department::query()->where('name', 'دائرة البحوث الاجتماعية')->exists()) {
            return;
        }

        $creatorId = User::query()->value('id');
        $companyId = Schema::hasColumn('employees_departments', 'company_id')
            ? Company::query()->value('id')
            : null;

        $socialResearch = Department::query()->create([
            'name'       => 'دائرة البحوث الاجتماعية',
            'company_id' => $companyId,
            'creator_id' => $creatorId,
        ]);

        foreach ([
            'قسم الاستقبال',
            'قسم خدمات الأسرة',
            'قسم التدقيق',
        ] as $sectionName) {
            Department::query()->create([
                'name'       => $sectionName,
                'parent_id'  => $socialResearch->id,
                'company_id' => $companyId,
                'creator_id' => $creatorId,
            ]);
        }

        $informationSystems = Department::query()->create([
            'name'       => 'دائرة نظم المعلومات',
            'company_id' => $companyId,
            'creator_id' => $creatorId,
        ]);

        Department::query()->create([
            'name'       => 'قسم نظم المعلومات',
            'parent_id'  => $informationSystems->id,
            'company_id' => $companyId,
            'creator_id' => $creatorId,
        ]);
    }
}
