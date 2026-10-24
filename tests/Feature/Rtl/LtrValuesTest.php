<?php

use Livewire\Livewire;
use Webkul\Security\Filament\Resources\CompanyResource\Pages\ViewCompany;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

it('keeps the phone number readable on the company page in arabic', function (): void {
    app()->setLocale('ar');

    $admin = User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $company = Company::factory()->create([
        'phone'  => '+96824123456',
        'mobile' => '+96899123456',
        'email'  => 'info@alwaha.example.om',
    ]);

    Livewire::test(ViewCompany::class, ['record' => $company->getKey()])
        ->assertSuccessful()
        ->assertSeeHtml('<bdi dir="ltr">+96824123456</bdi>')
        ->assertSeeHtml('<bdi dir="ltr">+96899123456</bdi>')
        ->assertSeeHtml('<bdi dir="ltr">info@alwaha.example.om</bdi>');
});
