<?php

namespace App\Filament\Projects\Pages;

use App\Filament\Concerns\InteractsWithAdvancedDashboard;
use App\Filament\Widgets\Projects\ProjectAverageCompletionWidget;
use App\Filament\Widgets\Projects\TopAssigneesWidget;
use App\Filament\Widgets\Projects\TopProjectsWidget;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Webkul\Partner\Models\Partner;
use Webkul\Project\Filament\Pages\Dashboard as BaseProjectDashboard;
use Webkul\Project\Filament\Widgets\StatsOverviewWidget;
use Webkul\Project\Filament\Widgets\TaskByStageChart;
use Webkul\Project\Filament\Widgets\TaskByStateChart;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\Tag;
use Webkul\Security\Models\User;

class ExtendedProjectDashboard extends BaseProjectDashboard
{
    use InteractsWithAdvancedDashboard;

    protected static ?string $slug = 'project';

    protected string $view = 'filament.pages.advanced-dashboard';

    public static function getNavigationLabel(): string
    {
        return __('dashboard.hub.projects');
    }

    public function getTitle(): string
    {
        return __('dashboard.hub.projects');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('dashboard.hub.projects_description');
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            $this->configureFilterSection(
                Section::make()
                    ->columns([
                        'default' => 1,
                        'sm'      => 2,
                        'md'      => 3,
                        'xl'      => 6,
                    ])
                    ->schema([
                        Select::make('selectedProjects')
                            ->label(__('projects::filament/pages/dashboard.filters-form.project'))
                            ->multiple()
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => Project::query()
                                ->where('name', 'like', "%{$search}%")
                                ->limit(50)
                                ->pluck('name', 'id')
                                ->all())
                            ->getOptionLabelsUsing(fn (array $values): array => Project::query()
                                ->whereIn('id', $values)
                                ->pluck('name', 'id')
                                ->all()),
                        Select::make('selectedAssignees')
                            ->label(__('projects::filament/pages/dashboard.filters-form.assignees'))
                            ->multiple()
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => User::query()
                                ->where('name', 'like', "%{$search}%")
                                ->limit(50)
                                ->pluck('name', 'id')
                                ->all())
                            ->getOptionLabelsUsing(fn (array $values): array => User::query()
                                ->whereIn('id', $values)
                                ->pluck('name', 'id')
                                ->all()),
                        Select::make('selectedTags')
                            ->label(__('projects::filament/pages/dashboard.filters-form.tags'))
                            ->multiple()
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => Tag::query()
                                ->where('name', 'like', "%{$search}%")
                                ->limit(50)
                                ->pluck('name', 'id')
                                ->all())
                            ->getOptionLabelsUsing(fn (array $values): array => Tag::query()
                                ->whereIn('id', $values)
                                ->pluck('name', 'id')
                                ->all()),
                        Select::make('selectedPartners')
                            ->label(__('projects::filament/pages/dashboard.filters-form.customer'))
                            ->multiple()
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => Partner::query()
                                ->where('name', 'like', "%{$search}%")
                                ->limit(50)
                                ->pluck('name', 'id')
                                ->all())
                            ->getOptionLabelsUsing(fn (array $values): array => Partner::query()
                                ->whereIn('id', $values)
                                ->pluck('name', 'id')
                                ->all()),
                        DatePicker::make('startDate')
                            ->label(__('projects::filament/pages/dashboard.filters-form.start-date'))
                            ->maxDate(fn (Get $get) => $get('endDate') ?: now())
                            ->default(now()->subMonth()->format('Y-m-d'))
                            ->native(false),
                        DatePicker::make('endDate')
                            ->label(__('projects::filament/pages/dashboard.filters-form.end-date'))
                            ->minDate(fn (Get $get) => $get('startDate') ?: now())
                            ->maxDate(now())
                            ->default(now())
                            ->native(false),
                        Select::make('projectStatus')
                            ->label(__('projects-extensions::filters.project_status'))
                            ->options([
                                ''          => __('projects-extensions::filters.all'),
                                'active'    => __('projects-extensions::filters.active'),
                                'completed' => __('projects-extensions::filters.completed'),
                                'cancelled' => __('projects-extensions::filters.cancelled'),
                            ])
                            ->native(false)
                            ->columnSpan([
                                'default' => 1,
                                'sm'      => 2,
                                'xl'      => 6,
                            ]),
                    ])
                    ->columnSpanFull(),
            ),
        ]);
    }

    public function getHeaderWidgets(): array
    {
        return [
            ProjectAverageCompletionWidget::class,
            StatsOverviewWidget::class,
        ];
    }

    public function getWidgets(): array
    {
        return [
            TaskByStageChart::class,
            TaskByStateChart::class,
            TopAssigneesWidget::class,
            TopProjectsWidget::class,
        ];
    }
}
