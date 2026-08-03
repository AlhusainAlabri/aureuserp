<?php

namespace Webkul\DocumentArchive\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Webkul\DocumentArchive\Models\DocFile;
use Webkul\DocumentArchive\Support\DocumentDashboardCache;

class DocumentTagService
{
    /**
     * @var array<string, string|null>
     */
    private array $pendingTagColors = [];

    public const DEFAULT_TAG_COLOR = '#64748b';

    public function __construct(
        private readonly DocumentAccessService $accessService,
    ) {}

    /**
     * @return array<string, string>
     */
    public function suggestions(): array
    {
        return DocumentDashboardCache::rememberGlobal('tag_suggestions', fn (): array => $this->buildSuggestions());
    }

    /**
     * @return array<string, string|null>
     */
    public function colorMap(): array
    {
        return DocumentDashboardCache::rememberGlobal('tag_color_map', fn (): array => $this->buildColorMap());
    }

    /**
     * @return array<string, string>
     */
    protected function buildSuggestions(): array
    {
        return DocFile::query()
            ->pluck('tags')
            ->filter()
            ->flatMap(function (?array $tags): Collection {
                return collect($tags)->map(function (array|string $tag): ?array {
                    if (is_string($tag)) {
                        return ['name' => $tag, 'color' => null];
                    }

                    $name = trim((string) ($tag['name'] ?? ''));

                    if ($name === '') {
                        return null;
                    }

                    return [
                        'name'  => $name,
                        'color' => $tag['color'] ?? null,
                    ];
                })->filter();
            })
            ->unique('name')
            ->sortBy('name')
            ->mapWithKeys(fn (array $tag): array => [$tag['name'] => $tag['name']])
            ->all();
    }

    /**
     * @return array<string, string|null>
     */
    protected function buildColorMap(): array
    {
        return DocFile::query()
            ->pluck('tags')
            ->filter()
            ->flatMap(function (?array $tags): Collection {
                return collect($tags)->map(function (array|string $tag): ?array {
                    if (is_string($tag)) {
                        return ['name' => $tag, 'color' => null];
                    }

                    $name = trim((string) ($tag['name'] ?? ''));

                    if ($name === '') {
                        return null;
                    }

                    return [
                        'name'  => $name,
                        'color' => $tag['color'] ?? null,
                    ];
                })->filter();
            })
            ->groupBy('name')
            ->map(fn (Collection $group): ?string => $group->firstWhere('color')['color'] ?? $group->first()['color'] ?? null)
            ->all();
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function syncTags(DocFile $file, array $input): void
    {
        $tags = $this->resolveTagsFromInput($input);

        $file->forceFill([
            'tags' => $this->accessService->normalizeTags($tags),
        ])->save();
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<int, array{name: string, color: string|null}|string>
     */
    public function resolveTagsFromInput(array $input): array
    {
        $colorMap = $this->colorMap();
        $customColors = collect($input['tag_colors'] ?? [])
            ->filter(fn ($color, $name): bool => filled($name))
            ->merge($this->pendingTagColors)
            ->all();

        if (array_key_exists('tag_names', $input)) {
            $tagNames = collect($input['tag_names'] ?? [])
                ->filter(fn ($name): bool => filled($name))
                ->values();

            return $tagNames
                ->map(fn (string $name): array => [
                    'name'  => $name,
                    'color' => $customColors[$name] ?? $colorMap[$name] ?? self::DEFAULT_TAG_COLOR,
                ])
                ->all();
        }

        if (! empty($input['tags'])) {
            return collect($input['tags'])
                ->map(function (array|string $tag) use ($colorMap, $customColors): ?array {
                    if (is_string($tag)) {
                        return [
                            'name'  => $tag,
                            'color' => $customColors[$tag] ?? $colorMap[$tag] ?? self::DEFAULT_TAG_COLOR,
                        ];
                    }

                    $name = trim((string) ($tag['name'] ?? ''));

                    if ($name === '') {
                        return null;
                    }

                    return [
                        'name'  => $name,
                        'color' => $tag['color'] ?? $customColors[$name] ?? $colorMap[$name] ?? self::DEFAULT_TAG_COLOR,
                    ];
                })
                ->filter()
                ->values()
                ->all();
        }

        return [];
    }

    /**
     * @return array<string, string>
     */
    public function pendingTagOptions(): array
    {
        return collect($this->pendingTagColors)
            ->mapWithKeys(fn (string $color, string $name): array => [$name => $name])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function optionsForSelect(): array
    {
        return array_merge($this->suggestions(), $this->pendingTagOptions());
    }

    public function rememberNewTagColor(string $name, ?string $color): void
    {
        $this->pendingTagColors[$name] = $color ?? self::DEFAULT_TAG_COLOR;
    }

    /**
     * @return array<string, mixed>
     */
    public function formDefaults(DocFile $file): array
    {
        return [
            'tag_names' => collect($file->getTagsWithColors())->pluck('name')->all(),
            'tags'      => $file->getTagsWithColors(),
        ];
    }

    /**
     * @param  array<int, string>  $tagNames
     */
    public function applyTagFilter(Builder $query, array $tagNames): Builder
    {
        return $query->withAnyTag($tagNames);
    }

    /**
     * @return array<string, int>
     */
    public function tagUsageCounts(?Builder $baseQuery = null, int $limit = 10): array
    {
        return collect($this->tagUsageCountsWithColors($baseQuery, $limit))
            ->mapWithKeys(fn (array $entry, string $name): array => [$name => $entry['count']])
            ->all();
    }

    /**
     * @return array<string, array{count: int, color: string}>
     */
    public function tagUsageCountsWithColors(?Builder $baseQuery = null, int $limit = 10): array
    {
        $query = $baseQuery ?? DocFile::query();

        return $this->extractTagsWithColorsFromQuery($query)
            ->groupBy('name')
            ->map(fn (Collection $group): array => [
                'count' => $group->count(),
                'color' => $group->first(fn (array $tag): bool => filled($tag['color'] ?? null))['color']
                    ?? $group->first()['color']
                    ?? self::DEFAULT_TAG_COLOR,
            ])
            ->sortByDesc('count')
            ->take($limit)
            ->all();
    }

    /**
     * @return Collection<int, array{name: string, color: string|null}>
     */
    protected function extractTagsWithColorsFromQuery(Builder $query): Collection
    {
        return $query->pluck('tags')
            ->filter()
            ->flatMap(function (?array $tags): Collection {
                return collect($tags)->map(function (array|string $tag): ?array {
                    if (is_string($tag)) {
                        return ['name' => $tag, 'color' => null];
                    }

                    $name = trim((string) ($tag['name'] ?? ''));

                    if ($name === '') {
                        return null;
                    }

                    return [
                        'name'  => $name,
                        'color' => $tag['color'] ?? null,
                    ];
                })->filter();
            });
    }

    protected function extractTagNamesFromQuery(Builder $query): Collection
    {
        return $this->extractTagsWithColorsFromQuery($query)
            ->pluck('name');
    }
}
