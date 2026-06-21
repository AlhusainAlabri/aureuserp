<?php

namespace App\Filament\Concerns;

trait HasEmployeeRelatedPageTranslations
{
    abstract protected static function employeeRelatedPageTranslationKey(): string;

    public function getBreadcrumb(): string
    {
        return static::getNavigationLabel();
    }

    public function getTitle(): string
    {
        return __(static::employeeRelatedPageTranslationKey().'.title', [
            'name' => $this->getRecord()->name,
        ]);
    }

    public function getTableEmptyStateHeading(): ?string
    {
        return __(static::employeeRelatedPageTranslationKey().'.empty.heading');
    }

    public function getTableEmptyStateDescription(): ?string
    {
        return __(static::employeeRelatedPageTranslationKey().'.empty.description');
    }
}
