<?php

namespace App\Filament\Concerns;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

trait InteractsWithAdvancedDashboard
{
    protected static bool $filtersCollapsedByDefault = true;

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    /**
     * @return array<int, array{label: string, description: string, url: string, icon: string, color: string}>
     */
    public function getDashboardHubLinks(): array
    {
        return [];
    }

    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'md'      => 2,
            'lg'      => 12,
        ];
    }

    protected function configureFilterSection(Section $section): Section
    {
        return $section
            ->heading(__('dashboard.filters.title'))
            ->description(__('dashboard.filters.description'))
            ->icon('heroicon-o-funnel')
            ->collapsible()
            ->collapsed(static::$filtersCollapsedByDefault)
            ->persistCollapsed();
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema;
    }
}
