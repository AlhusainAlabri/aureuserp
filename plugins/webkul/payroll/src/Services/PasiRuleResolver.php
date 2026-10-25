<?php

namespace Webkul\Payroll\Services;

use Carbon\CarbonInterface;
use Webkul\Employee\Models\Employee;
use Webkul\Payroll\Enums\PasiCategory;
use Webkul\Payroll\Models\PasiRule;

class PasiRuleResolver
{
    public function categoryFor(Employee $employee): ?PasiCategory
    {
        $employee->loadMissing('company');

        $nationalityId = $employee->country_id;
        $companyCountryId = $employee->company?->country_id;

        if ($nationalityId === null || $companyCountryId === null) {
            return null;
        }

        return (int) $nationalityId === (int) $companyCountryId
            ? PasiCategory::Omani
            : PasiCategory::Expat;
    }

    public function ruleFor(Employee $employee, CarbonInterface $date): ?PasiRule
    {
        $category = $this->categoryFor($employee);

        if ($category === null) {
            return null;
        }

        return PasiRule::query()
            ->where('category', $category->value)
            ->inForce($date)
            ->orderByDesc('effective_from')
            ->first();
    }
}
