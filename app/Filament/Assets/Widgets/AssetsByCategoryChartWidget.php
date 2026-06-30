<?php

namespace App\Filament\Assets\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Schema;
use Webkul\Assets\Enums\AssetCategory;
use Webkul\Assets\Models\Asset;

class AssetsByCategoryChartWidget extends ChartWidget
{
    protected static ?int $sort = 5;

    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '300px';

    protected int|string|array $columnSpan = 6;

    public function getHeading(): ?string
    {
        return __('assets-extensions::dashboard.by_category');
    }

    protected function getData(): array
    {
        if (! Schema::hasTable('assets')) {
            return [
                'datasets' => [[
                    'data'            => [1],
                    'backgroundColor' => ['#9CA3AF'],
                ]],
                'labels' => [__('assets::assets.widgets.stats.plugin_not_installed')],
            ];
        }

        $rows = Asset::query()
            ->selectRaw('category, COUNT(*) as total')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->groupBy('category')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        if ($rows->isEmpty()) {
            return [
                'datasets' => [[
                    'data'            => [1],
                    'backgroundColor' => ['#9CA3AF'],
                ]],
                'labels' => [__('assets-extensions::dashboard.no_categories')],
            ];
        }

        $colors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#06B6D4', '#84CC16'];

        return [
            'datasets' => [[
                'label'           => __('assets-extensions::dashboard.asset_count'),
                'data'            => $rows->pluck('total')->map(fn ($value): int => (int) $value)->all(),
                'backgroundColor' => array_slice($colors, 0, $rows->count()),
            ]],
            'labels' => $rows->map(function ($row): string {
                $category = $row->getAttributes()['category'] ?? null;

                return AssetCategory::tryFrom((string) $category)?->getLabel()
                    ?? (string) ($category ?? '—');
            })->all(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
