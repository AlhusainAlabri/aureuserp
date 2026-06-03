<?php

namespace App\Support;

use App\Filament\Pages\Dashboard as OrgDashboard;
use Filament\Navigation\NavigationItem;
use Illuminate\Support\Collection;
use Webkul\Project\Filament\Pages\Dashboard as ProjectDashboard;
use Webkul\Recruitment\Filament\Pages\Recruitments as RecruitmentDashboard;

class DashboardNavigationOrder
{
    /**
     * Topbar order for pages registered under the executive dashboard navigation group.
     * Webkul module dashboards extend Filament\Pages\Dashboard and share one static sort value,
     * so ordering is applied here by canonical page URL.
     *
     * @var array<class-string, int>
     */
    private const ORDER_BY_PAGE = [
        OrgDashboard::class         => 0,
        ProjectDashboard::class     => 10,
        RecruitmentDashboard::class => 20,
    ];

    /**
     * @param  iterable<NavigationItem>  $items
     * @return Collection<int, NavigationItem>
     */
    public static function sort(iterable $items): Collection
    {
        $sortByUrl = collect(self::ORDER_BY_PAGE)
            ->mapWithKeys(fn (int $sort, string $pageClass): array => [
                rtrim(parse_url($pageClass::getUrl(), PHP_URL_PATH) ?? '', '/') => $sort,
            ]);

        return collect($items)
            ->sortBy(function (NavigationItem $item) use ($sortByUrl): int {
                $path = rtrim(parse_url($item->getUrl(), PHP_URL_PATH) ?? '', '/');

                return $sortByUrl->get($path, 100);
            })
            ->values();
    }
}
