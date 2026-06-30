<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<string, string>
     */
    protected array $departmentNameMap = [
        'Administration'                  => 'الإدارة',
        'Long Term Projects'              => 'مشاريع طويلة المدى',
        'Management'                      => 'الإدارة العليا',
        'Professional Services'           => 'الخدمات المهنية',
        'R&D USA'                         => 'البحث والتطوير — أمريكا',
        'Research & Development'          => 'البحث والتطوير',
        'Sales'                           => 'المبيعات',
        'Social Research Department'      => 'دائرة البحوث الاجتماعية',
        'Reception Section'               => 'قسم الاستقبال',
        'Family Services Section'         => 'قسم خدمات الأسرة',
        'Audit Section'                   => 'قسم التدقيق',
        'Information Systems Department'  => 'دائرة نظم المعلومات',
        'Information Systems Section'     => 'قسم نظم المعلومات',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('employees_departments')) {
            return;
        }

        foreach ($this->departmentNameMap as $englishName => $arabicName) {
            DB::table('employees_departments')
                ->where('name', $englishName)
                ->update(['name' => $arabicName]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('employees_departments')) {
            return;
        }

        foreach ($this->departmentNameMap as $englishName => $arabicName) {
            DB::table('employees_departments')
                ->where('name', $arabicName)
                ->update(['name' => $englishName]);
        }
    }
};
