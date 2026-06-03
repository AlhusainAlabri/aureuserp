<?php

namespace App\Providers;

use App\Filament\Projects\Resources\ProjectResource\Pages\ListProjects as ExtendedListProjects;
use App\Models\Projects\TaskCategory;
use App\Observers\ProjectTaskObserver;
use App\Services\Projects\ProjectCompletionService;
use App\Services\Projects\ProjectFinancialSummaryService;
use App\Services\Projects\TaskNotificationService;
use App\Services\Projects\UnifiedTaskQueryService;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Page;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use ReflectionClass;
use Webkul\Account\Models\Move;
use Webkul\DocumentArchive\Models\DocFile;
use Webkul\Employee\Models\Department;
use Webkul\Project\Filament\Clusters\Configurations;
use Webkul\Project\Filament\Clusters\Settings\Pages\ManageTasks as ProjectManageTasksSettings;
use Webkul\Project\Filament\Resources\ProjectResource\Pages\ListProjects as BaseListProjects;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\Task;
use Webkul\Purchase\Models\Order;
use Webkul\Security\Models\User;

class ProjectExtensionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->restoreGlobalPageNavigationRegistration();

        $this->registerConfigurationsClusterOverride();

        $this->app->singleton(ProjectCompletionService::class);
        $this->app->singleton(ProjectFinancialSummaryService::class);
        $this->app->singleton(TaskNotificationService::class);
        $this->app->singleton(UnifiedTaskQueryService::class);
    }

    public function boot(): void
    {
        $this->restoreGlobalPageNavigationRegistration();

        $this->loadTranslationsFrom(lang_path('projects-extensions'), 'projects-extensions');

        $this->app->booted(function (): void {
            $this->registerLivewireOverrides();
        });

        Filament::serving(function (): void {
            $this->registerLivewireOverrides();
            $this->hideProjectManageTasksSettingsFromMainNavigation();
        });

        if (! class_exists(Task::class)) {
            return;
        }

        $this->registerProjectRelations();
        $this->registerMoveRelation();
        $this->registerTaskRelations();
        Task::observe(ProjectTaskObserver::class);
    }

    protected function registerProjectRelations(): void
    {
        if (! class_exists(Project::class)) {
            return;
        }

        if (class_exists(Order::class)) {
            Project::resolveRelationUsing('orders', fn (Project $project) => $project->hasMany(Order::class, 'project_id'));
        }

        if (class_exists(DocFile::class)) {
            Project::resolveRelationUsing('docFiles', fn (Project $project) => $project->hasMany(DocFile::class, 'project_id'));
        }

        if (class_exists(Move::class)) {
            Project::resolveRelationUsing('accountMoves', fn (Project $project) => $project->hasMany(Move::class, 'project_id'));
        }
    }

    protected function registerMoveRelation(): void
    {
        if (! class_exists(Move::class)) {
            return;
        }

        Move::resolveRelationUsing('project', fn (Move $move) => $move->belongsTo(Project::class, 'project_id'));
    }

    protected function registerTaskRelations(): void
    {
        Task::resolveRelationUsing('owner', fn (Task $task) => $task->belongsTo(User::class, 'owner_id'));
        Task::resolveRelationUsing('category', fn (Task $task) => $task->belongsTo(TaskCategory::class, 'category_id'));

        if (class_exists(Department::class)) {
            Task::resolveRelationUsing('department', fn (Task $task) => $task->belongsTo(Department::class, 'department_id'));
        }
    }

    protected function restoreGlobalPageNavigationRegistration(): void
    {
        $reflection = new ReflectionClass(Page::class);
        $property = $reflection->getProperty('shouldRegisterNavigation');
        $property->setAccessible(true);
        $property->setValue(null, true);
    }

    protected function registerConfigurationsClusterOverride(): void
    {
        spl_autoload_register(
            function (string $class): bool {
                if ($class !== Configurations::class) {
                    return false;
                }

                require app_path('Overrides/Webkul/Project/Filament/Clusters/Configurations.php');

                return true;
            },
            prepend: true,
        );
    }

    protected function hideProjectManageTasksSettingsFromMainNavigation(): void
    {
        if (! class_exists(ProjectManageTasksSettings::class)) {
            return;
        }

        $panel = Filament::getCurrentPanel();

        if (! $panel || $panel->getId() !== 'admin') {
            return;
        }

        $manageTasksUrl = ProjectManageTasksSettings::getUrl();

        $reflection = new ReflectionClass($panel);
        $property = $reflection->getProperty('navigationItems');
        $property->setAccessible(true);

        $items = $property->getValue($panel);

        $property->setValue(
            $panel,
            array_values(array_filter(
                $items,
                fn (NavigationItem $item): bool => $item->getUrl() !== $manageTasksUrl,
            )),
        );
    }

    protected function registerLivewireOverrides(): void
    {
        if (class_exists(BaseListProjects::class)) {
            Livewire::component(
                'webkul.project.filament.resources.project-resource.pages.list-projects',
                ExtendedListProjects::class,
            );

            Livewire::component(
                BaseListProjects::class,
                ExtendedListProjects::class,
            );
        }

    }
}
