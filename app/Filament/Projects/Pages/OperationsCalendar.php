<?php

namespace App\Filament\Projects\Pages;

use App\Filament\Projects\Widgets\OperationsCalendarWidget;
use App\Support\FilamentUrl;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Schema;

class OperationsCalendar extends BaseDashboard
{
    use HasPageShield;

    protected static string $routePath = 'projects/task-hub/calendar';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?int $navigationSort = 4;

    protected static bool $shouldRegisterNavigation = false;

    protected ?string $pollingInterval = null;

    protected static function getPagePermission(): ?string
    {
        return 'page_operations_calendar';
    }

    public static function canAccess(array $parameters = []): bool
    {
        return Schema::hasTable('projects_tasks')
            && parent::canAccess($parameters);
    }

    public function getTitle(): string
    {
        return __('tasks.calendar.title');
    }

    public function getSubheading(): ?string
    {
        return __('tasks.calendar.subheading');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('hub')
                ->label(__('tasks.navigation.hub'))
                ->icon('heroicon-o-clipboard-document-check')
                ->url(fn (): string => FilamentUrl::appendLocaleToUrl(TaskOperationsHub::getUrl()))
                ->color('gray'),
            Action::make('kanban')
                ->label(__('tasks.hub.view_kanban'))
                ->icon('heroicon-o-view-columns')
                ->url(fn (): string => FilamentUrl::appendLocaleToUrl(TaskKanban::getUrl()))
                ->color('gray'),
        ];
    }

    public function getWidgets(): array
    {
        return [
            OperationsCalendarWidget::class,
        ];
    }
}
