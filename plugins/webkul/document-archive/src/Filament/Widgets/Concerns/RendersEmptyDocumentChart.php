<?php

namespace Webkul\DocumentArchive\Filament\Widgets\Concerns;

use Illuminate\Contracts\Support\Htmlable;

trait RendersEmptyDocumentChart
{
    /**
     * @return array{datasets: list<array<string, mixed>>, labels: list<string>}
     */
    protected function emptyChartData(): array
    {
        return [
            'datasets' => [[
                'data'            => [1],
                'backgroundColor' => ['#E5E7EB'],
                'borderWidth'     => 0,
            ]],
            'labels' => [__('document-archive::document-archive.dashboard.charts.empty_label')],
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    protected function applyEmptyChartOptions(array $options): array
    {
        $options['plugins']['tooltip'] = [
            'enabled' => false,
        ];

        if (isset($options['scales']) && is_array($options['scales'])) {
            foreach ($options['scales'] as $axis => $scale) {
                if (! is_array($scale)) {
                    continue;
                }

                $options['scales'][$axis]['display'] = false;
            }
        }

        return $options;
    }

    public function getDescription(): string|Htmlable|null
    {
        if (! $this->hasChartData()) {
            return __('document-archive::document-archive.dashboard.charts.empty');
        }

        return $this->getChartDescription();
    }

    protected function getChartDescription(): string|Htmlable|null
    {
        return null;
    }

    abstract protected function hasChartData(): bool;
}
