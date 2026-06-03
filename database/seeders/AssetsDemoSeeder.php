<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Webkul\Assets\Enums\AssetCategory;
use Webkul\Assets\Enums\AssetStatus;
use Webkul\Assets\Enums\BorrowingStatus;
use Webkul\Assets\Models\Asset;
use Webkul\Assets\Models\AssetBorrowing;
use Webkul\Employee\Models\Employee;
use Webkul\Support\Models\Company;

class AssetsDemoSeeder extends Seeder
{
    private const DEMO_SERIAL_PREFIX = 'DEMO-AST-';

    /**
     * @var array<int, array{en: string, ar: string, category: AssetCategory, status: AssetStatus, value: float, location_en: string, location_ar: string, plate_number?: string}>
     */
    private array $demoAssets = [
        [
            'en'           => 'Toyota Hilux — Committee Fleet',
            'ar'           => 'تويوتا هايلكس — أسطول اللجنة',
            'category'     => AssetCategory::Vehicle,
            'status'       => AssetStatus::Available,
            'value'        => 12500.000,
            'location_en'  => 'Main building parking',
            'location_ar'  => 'موقف المبنى الرئيسي',
            'plate_number' => 'A 12345',
        ],
        [
            'en'          => 'Executive Office Desk',
            'ar'          => 'مكتب تنفيذي',
            'category'    => AssetCategory::Furniture,
            'status'      => AssetStatus::Available,
            'value'       => 450.500,
            'location_en' => 'Floor 2 — Admin',
            'location_ar' => 'الطابق 2 — الإدارة',
        ],
        [
            'en'          => 'Conference Room Projector',
            'ar'          => 'جهاز عرض — قاعة الاجتماعات',
            'category'    => AssetCategory::Equipment,
            'status'      => AssetStatus::Maintenance,
            'value'       => 890.750,
            'location_en' => 'Store room',
            'location_ar' => 'غرفة التخزين',
        ],
        [
            'en'          => 'HP Laser Printer',
            'ar'          => 'طابعة ليزر HP',
            'category'    => AssetCategory::Equipment,
            'status'      => AssetStatus::Available,
            'value'       => 320.000,
            'location_en' => 'Reception',
            'location_ar' => 'الاستقبال',
        ],
        [
            'en'          => 'Meeting Room Chairs (set of 8)',
            'ar'          => 'كراسي قاعة الاجتماعات (8)',
            'category'    => AssetCategory::Furniture,
            'status'      => AssetStatus::Retired,
            'value'       => 600.000,
            'location_en' => 'Warehouse',
            'location_ar' => 'المستودع',
        ],
    ];

    public function run(): void
    {
        if (! Schema::hasTable('assets')) {
            return;
        }

        if (Asset::query()->where('serial_number', 'like', self::DEMO_SERIAL_PREFIX.'%')->exists()) {
            return;
        }

        $companyId = Company::query()->value('id');
        $employee = Employee::query()->first();
        $locale = app()->getLocale();
        $useArabic = $locale === 'ar';

        $availableAsset = null;

        foreach ($this->demoAssets as $index => $item) {
            $asset = Asset::factory()->create([
                'name'          => $useArabic ? $item['ar'] : $item['en'],
                'category'      => $item['category']->value,
                'serial_number' => self::DEMO_SERIAL_PREFIX.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                'status'        => $item['status'],
                'value'         => $item['value'],
                'location'      => $useArabic ? $item['location_ar'] : $item['location_en'],
                'company_id'    => $companyId,
                'plate_number'  => $item['plate_number'] ?? null,
            ]);

            if ($item['status'] === AssetStatus::Available && $availableAsset === null) {
                $availableAsset = $asset;
            }
        }

        if ($employee === null || $availableAsset === null || ! Schema::hasTable('asset_borrowings')) {
            return;
        }

        $borrowedAsset = Asset::factory()->create([
            'name'          => $useArabic ? 'جهاز كمبيوتر محمول — موظف' : 'Employee Laptop',
            'category'      => AssetCategory::Equipment->value,
            'serial_number' => self::DEMO_SERIAL_PREFIX.'999',
            'status'        => AssetStatus::Borrowed,
            'value'         => 750.000,
            'location'      => $useArabic ? 'مكتب الموظف' : 'Employee desk',
            'company_id'    => $companyId,
        ]);

        AssetBorrowing::query()->create([
            'asset_id'    => $borrowedAsset->id,
            'employee_id' => $employee->id,
            'borrowed_at' => now()->subDays(3),
            'due_at'      => now()->addDays(4),
            'status'      => BorrowingStatus::Active,
            'notes'       => $useArabic ? 'إعارة تجريبية — مستحق قريباً' : 'Demo borrowing — due soon',
        ]);

        $overdueAsset = Asset::factory()->create([
            'name'          => $useArabic ? 'كاميرا فعاليات' : 'Events Camera',
            'category'      => AssetCategory::Equipment->value,
            'serial_number' => self::DEMO_SERIAL_PREFIX.'998',
            'status'        => AssetStatus::Borrowed,
            'value'         => 1100.000,
            'location'      => $useArabic ? 'قسم الإعلام' : 'Media department',
            'company_id'    => $companyId,
        ]);

        AssetBorrowing::query()->create([
            'asset_id'    => $overdueAsset->id,
            'employee_id' => $employee->id,
            'borrowed_at' => now()->subDays(14),
            'due_at'      => now()->subDays(2),
            'status'      => BorrowingStatus::Overdue,
            'notes'       => $useArabic ? 'إعارة متأخرة — للاختبار' : 'Overdue demo borrowing',
        ]);

        AssetBorrowing::query()->create([
            'asset_id'    => $availableAsset->id,
            'employee_id' => $employee->id,
            'borrowed_at' => now()->subDays(30),
            'due_at'      => now()->subDays(20),
            'returned_at' => now()->subDays(18),
            'status'      => BorrowingStatus::Returned,
            'notes'       => $useArabic ? 'إعارة مُرجعة — سجل تاريخي' : 'Returned demo borrowing',
        ]);

        $availableAsset->update(['status' => AssetStatus::Available]);
    }
}
