<?php

namespace App\Filament\Extensions;

use App\Filament\RelationManagers\EnhancedProjectMeetingsRelationManager;
use App\Filament\RelationManagers\ProjectDocumentsRelationManager;
use App\Filament\RelationManagers\ProjectInvoicesRelationManager;
use App\Filament\RelationManagers\ProjectOrdersRelationManager;
use App\Services\Projects\ProjectCompletionService;
use App\Services\Projects\ProjectFinancialSummaryService;
use App\Services\Projects\ProjectStageHelper;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Webkul\Correspondence\Filament\Resources\ProjectCorrespondencesRelationManager;
use Webkul\Project\Enums\TaskState;
use Webkul\Project\Models\Project;
use Webkul\TableViews\Filament\Components\PresetView;

class ProjectResourceExtensions
{
    public static function getModelLabel(): string
    {
        return __('projects::models/project.title');
    }

    public static function getPluralModelLabel(): string
    {
        return __('projects::filament/resources/project.navigation.title');
    }

    /** @return array<int, mixed> */
    public static function kpiInfolistSection(): array
    {
        if (! Schema::hasTable('projects_projects')) {
            return [];
        }

        return [
            Section::make(__('projects-extensions::kpi.section_title'))
                ->schema([
                    Grid::make(3)
                        ->schema([
                            TextEntry::make('completion_percentage')
                                ->label(__('projects-extensions::kpi.completion'))
                                ->state(function (Project $record): string {
                                    return app(ProjectCompletionService::class)->formatPercentage(
                                        app(ProjectCompletionService::class)->calculate($record),
                                    );
                                })
                                ->icon('heroicon-o-chart-bar')
                                ->color('primary')
                                ->columnSpan(1),
                            TextEntry::make('financial_purchase_total')
                                ->label(__('projects-extensions::kpi.purchase_total'))
                                ->state(function (Project $record): string {
                                    $summary = app(ProjectFinancialSummaryService::class)->summarize($record);

                                    return app(ProjectFinancialSummaryService::class)->formatOmr($summary['purchase_total']);
                                })
                                ->icon('heroicon-o-shopping-cart')
                                ->visible(fn (): bool => Schema::hasTable('purchases_orders'))
                                ->columnSpan(1),
                            TextEntry::make('financial_invoice_total')
                                ->label(__('projects-extensions::kpi.invoice_total'))
                                ->state(function (Project $record): string {
                                    $summary = app(ProjectFinancialSummaryService::class)->summarize($record);

                                    return app(ProjectFinancialSummaryService::class)->formatOmr($summary['invoice_total']);
                                })
                                ->icon('heroicon-o-banknotes')
                                ->visible(fn (): bool => Schema::hasTable('accounts_account_moves'))
                                ->columnSpan(1),
                            TextEntry::make('financial_grand_total')
                                ->label(__('projects-extensions::kpi.grand_total'))
                                ->state(function (Project $record): string {
                                    $summary = app(ProjectFinancialSummaryService::class)->summarize($record);

                                    return app(ProjectFinancialSummaryService::class)->formatOmr($summary['grand_total']);
                                })
                                ->icon('heroicon-o-calculator')
                                ->weight('bold')
                                ->columnSpan(1),
                            TextEntry::make('meetings_count')
                                ->label(__('projects-extensions::kpi.meetings_count'))
                                ->state(fn (Project $record): int => Schema::hasTable('meetings') ? $record->meetings()->count() : 0)
                                ->icon('heroicon-o-clipboard-document-list')
                                ->visible(fn (): bool => Schema::hasTable('meetings'))
                                ->columnSpan(1),
                            TextEntry::make('documents_count')
                                ->label(__('projects-extensions::kpi.documents_count'))
                                ->state(fn (Project $record): int => Schema::hasTable('doc_files') ? $record->docFiles()->count() : 0)
                                ->icon('heroicon-o-document')
                                ->visible(fn (): bool => Schema::hasTable('doc_files'))
                                ->columnSpan(1),
                            TextEntry::make('open_tasks_count')
                                ->label(__('projects-extensions::kpi.tasks_open'))
                                ->state(fn (Project $record): int => $record->tasks()
                                    ->whereNull('parent_id')
                                    ->whereNotIn('state', [TaskState::DONE->value, TaskState::CANCELLED->value])
                                    ->count())
                                ->icon('heroicon-o-queue-list')
                                ->columnSpan(1),
                            TextEntry::make('timeline_health')
                                ->label(__('projects-extensions::columns.date'))
                                ->state(function (Project $record): string {
                                    if (! $record->end_date) {
                                        return '—';
                                    }

                                    return $record->end_date->isPast()
                                        ? __('projects-extensions::kpi.overdue')
                                        : __('projects-extensions::kpi.on_track');
                                })
                                ->badge()
                                ->color(fn (Project $record): string => $record->end_date?->isPast() ? 'danger' : 'success')
                                ->columnSpan(1),
                        ]),
                ])
                ->columnSpanFull(),
        ];
    }

    /** @return array<int, Stack> */
    public static function extraTableStacks(): array
    {
        if (! Schema::hasTable('projects_projects')) {
            return [];
        }

        return [
            Stack::make([
                TextColumn::make('stage.name')
                    ->label(__('projects-extensions::columns.stage'))
                    ->badge(),
                TextColumn::make('completion_percentage')
                    ->label(__('projects-extensions::columns.completion'))
                    ->badge()
                    ->color('primary')
                    ->state(fn (Project $record): string => app(ProjectCompletionService::class)->formatPercentage(
                        app(ProjectCompletionService::class)->calculate($record),
                    )),
            ]),
        ];
    }

    /** @return array<int, TextColumn> */
    public static function extraTableColumns(): array
    {
        if (! Schema::hasTable('projects_projects')) {
            return [];
        }

        return [
            TextColumn::make('stage.name')
                ->label(__('projects-extensions::columns.stage'))
                ->badge()
                ->toggleable(),
            TextColumn::make('completion_percentage')
                ->label(__('projects-extensions::columns.completion'))
                ->state(fn (Project $record): string => app(ProjectCompletionService::class)->formatPercentage(
                    app(ProjectCompletionService::class)->calculate($record),
                ))
                ->toggleable(),
            TextColumn::make('user.name')
                ->label(__('projects-extensions::columns.manager'))
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    /** @return array<string, PresetView> */
    public static function presetTableViews(): array
    {
        if (! Schema::hasTable('projects_projects') || ! ProjectStageHelper::isAvailable()) {
            return [];
        }

        return [
            'active_projects' => PresetView::make(__('projects-extensions::stages.active'))
                ->icon('heroicon-s-play')
                ->modifyQueryUsing(function (Builder $query): Builder {
                    return ProjectStageHelper::applyStageFilter(
                        $query->where('is_active', true),
                        'in_progress',
                    );
                }),
            'completed_projects' => PresetView::make(__('projects-extensions::stages.completed'))
                ->icon('heroicon-s-check-circle')
                ->modifyQueryUsing(fn (Builder $query): Builder => ProjectStageHelper::applyStageFilter($query, 'done')),
            'cancelled_projects' => PresetView::make(__('projects-extensions::stages.cancelled'))
                ->icon('heroicon-s-x-circle')
                ->modifyQueryUsing(fn (Builder $query): Builder => ProjectStageHelper::applyStageFilter($query, 'cancelled')),
        ];
    }

    /** @return array<int, mixed> */
    public static function extraRelationGroups(): array
    {
        $groups = [];

        if (Schema::hasTable('purchases_orders')) {
            $groups[] = RelationGroup::make(__('projects-extensions::project-relations.orders'), [
                ProjectOrdersRelationManager::class,
            ])->icon('heroicon-o-shopping-cart');
        }

        if (Schema::hasTable('accounts_account_moves') && Schema::hasColumn('accounts_account_moves', 'project_id')) {
            $groups[] = RelationGroup::make(__('projects-extensions::project-relations.invoices'), [
                ProjectInvoicesRelationManager::class,
            ])->icon('heroicon-o-banknotes');
        }

        if (Schema::hasTable('doc_files')) {
            $groups[] = RelationGroup::make(__('projects-extensions::project-relations.documents'), [
                ProjectDocumentsRelationManager::class,
            ])->icon('heroicon-o-document');
        }

        if (Schema::hasTable('meetings')) {
            $groups[] = RelationGroup::make(__('projects-extensions::project-relations.meetings'), [
                EnhancedProjectMeetingsRelationManager::class,
            ])->icon('heroicon-o-clipboard-document-list');
        }

        if (Schema::hasTable('correspondences') && class_exists(ProjectCorrespondencesRelationManager::class)) {
            $groups[] = RelationGroup::make(__('correspondence::correspondence.relations.project_correspondences'), [
                ProjectCorrespondencesRelationManager::class,
            ])->icon('heroicon-o-envelope');
        }

        return $groups;
    }
}
