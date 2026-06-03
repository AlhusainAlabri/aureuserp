<?php

use Livewire\Livewire;
use Webkul\Employee\Filament\Resources\SubmissionResource;
use Webkul\Employee\Filament\Resources\SubmissionResource\Pages\ListSubmissions;

require_once __DIR__.'/../Employees/EmployeeTestHelpers.php';

it('uses internal requests terminology for submissions navigation in arabic', function (): void {
    if (! class_exists(SubmissionResource::class)) {
        $this->markTestSkipped('Employees plugin is not installed.');
    }

    app()->setLocale('ar');

    expect(SubmissionResource::getNavigationLabel())->toBe('الطلبات الداخلية')
        ->and(SubmissionResource::getPluralModelLabel())->toBe('الطلبات الداخلية')
        ->and(__('employees::filament/resources/submission.empty.heading'))->toBe('لا توجد طلبات')
        ->and(__('filament-tables::table.fields.search.label'))->toBe('بحث');
});

it('shows arabic labels on the submissions list page', function (): void {
    if (! class_exists(ListSubmissions::class)) {
        $this->markTestSkipped('Employees plugin is not installed.');
    }

    app()->setLocale('ar');

    $user = createEmployeeAdminUser([
        'view_any_employee_submission',
    ]);

    $this->actingAs($user);

    Livewire::test(ListSubmissions::class)
        ->assertSuccessful()
        ->assertSee('الكل')
        ->assertSee('مفتوح')
        ->assertSee('قيد المراجعة')
        ->assertSee('رقم الطلب')
        ->assertSee('النوع')
        ->assertSee('الحالة');
});
