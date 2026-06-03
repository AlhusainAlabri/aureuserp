<?php

namespace App\Filament\Contacts\Concerns;

use Filament\Actions\Action;
use Illuminate\Contracts\Support\Htmlable;
use Webkul\Contact\Filament\Clusters\Configurations;
use Webkul\Contact\Filament\Resources\PartnerResource;

trait HasContactConfigurationBreadcrumbs
{
    public function getTitle(): string|Htmlable
    {
        return static::getResource()::getNavigationLabel();
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return __('filament-tables::table.empty.heading', [
            'model' => static::getResource()::getPluralModelLabel(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [
            PartnerResource::getUrl('index') => PartnerResource::getNavigationLabel(),
            Configurations::getUrl()         => Configurations::getClusterBreadcrumb(),
        ];

        $current = $this->getBreadcrumb() ?? $this->getTitle();

        if (filled($current)) {
            $breadcrumbs[] = $current;
        }

        return $breadcrumbs;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('backToContacts')
                ->label(__('contacts-extensions::actions.back_to_contacts'))
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('gray')
                ->url(PartnerResource::getUrl('index')),
            ...parent::getHeaderActions(),
        ];
    }
}
