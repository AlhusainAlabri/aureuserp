<?php

namespace App\Support;

use Filament\Facades\Filament;
use Filament\GlobalSearch\GlobalSearchResults;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;
use Webkul\Support\GlobalSearchProvider as BaseGlobalSearchProvider;

class GlobalSearchProvider extends BaseGlobalSearchProvider
{
    public function getResults(string $query): ?GlobalSearchResults
    {
        $builder = GlobalSearchResults::make();

        $resources = Filament::getResources();

        usort(
            $resources,
            fn (string $a, string $b): int => ($a::getGlobalSearchSort() ?? 0) <=> ($b::getGlobalSearchSort() ?? 0),
        );

        foreach ($resources as $resource) {
            if (! $resource::canGloballySearch()) {
                continue;
            }

            try {
                $resourceResults = $resource::getGlobalSearchResults($query);
            } catch (Throwable $exception) {
                Log::warning('Global search failed for resource.', [
                    'resource' => $resource,
                    'query'    => $query,
                    'message'  => $exception->getMessage(),
                ]);

                continue;
            }

            if (! $resourceResults->count()) {
                continue;
            }

            $pluginKey = Str::of($resource)
                ->after('\\')
                ->before('\\')
                ->toString();

            $moduleKey = 'global-search.modules.'.Str::snake($pluginKey);
            $module = Lang::has($moduleKey) ? __($moduleKey) : $pluginKey;

            $builder->category($module.' → '.$resource::getPluralModelLabel(), $resourceResults);
        }

        return $builder;
    }
}
