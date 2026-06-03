<?php

namespace App\Filament\Widgets\Dashboard\Concerns;

use Filament\Widgets\StatsOverviewWidget\Stat;

trait ConfiguresClickableStat
{
    protected function clickableStat(
        string $label,
        string|int|float|null $value,
        ?string $url = null,
        ?string $description = null,
        ?string $descriptionIcon = null,
        string $color = 'primary',
        ?string $icon = null,
    ): Stat {
        $stat = Stat::make($label, $value)
            ->color($color);

        if ($description !== null) {
            $stat->description($description);
        }

        if ($descriptionIcon !== null) {
            $stat->descriptionIcon($descriptionIcon);
        }

        if ($icon !== null) {
            $stat->icon($icon);
        }

        if ($url !== null) {
            $stat
                ->url($url)
                ->extraAttributes(['class' => 'cursor-pointer']);
        }

        return $stat;
    }
}
