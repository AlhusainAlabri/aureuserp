<?php

namespace Webkul\Payroll\Concerns;

trait HasLocalizedDisplayName
{
    public function getDisplayNameAttribute(): string
    {
        if (app()->getLocale() === 'ar' && filled($this->name_ar ?? null)) {
            return (string) $this->name_ar;
        }

        return (string) ($this->name ?? '');
    }
}
