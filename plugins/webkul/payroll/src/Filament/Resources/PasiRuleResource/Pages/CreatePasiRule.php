<?php

namespace Webkul\Payroll\Filament\Resources\PasiRuleResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Webkul\Payroll\Enums\PasiCategory;
use Webkul\Payroll\Filament\Resources\PasiRuleResource;
use Webkul\Payroll\Services\PasiRuleManager;

class CreatePasiRule extends CreateRecord
{
    protected static string $resource = PasiRuleResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(PasiRuleManager::class)->addRule(
                $data['category'] instanceof PasiCategory ? $data['category'] : PasiCategory::from($data['category']),
                (float) $data['employee_rate'],
                (float) $data['employer_rate'],
                filled($data['salary_cap'] ?? null) ? (float) $data['salary_cap'] : null,
                Carbon::parse($data['effective_from']),
            );
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages([
                'data.effective_from' => $exception->errors()['effective_from'] ?? [__('payroll::payroll.pasi.overlap')],
            ]);
        }
    }
}
