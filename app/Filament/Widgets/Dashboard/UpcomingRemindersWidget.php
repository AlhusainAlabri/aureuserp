<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Widgets\Dashboard\Concerns\BuildsEmptyTableQueries;
use App\Filament\Widgets\Dashboard\Concerns\HasOrgDashboardTableLayout;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Schema;
use Webkul\MyNotes\Models\Note;

class UpcomingRemindersWidget extends BaseWidget
{
    use BuildsEmptyTableQueries;
    use HasOrgDashboardTableLayout;
    use InteractsWithPageFilters;

    protected static ?int $sort = 21;

    protected static bool $isLazy = true;

    public function getTableHeading(): string|Htmlable|null
    {
        return __('dashboard.widgets.reminders');
    }

    public function table(Table $table): Table
    {
        if (! Schema::hasTable('notes')) {
            return $table
                ->query($this->emptyTableQuery(Note::class, [
                    'id'            => '0',
                    'title'         => "''",
                    'auto_title'    => "''",
                    'reminder_at'   => 'null',
                    'color_hex'     => "''",
                    'type'          => "''",
                    'reminder_sent' => '0',
                    'user_id'       => '0',
                    'deleted_at'    => 'null',
                ]))
                ->columns([TextColumn::make('title')])
                ->emptyStateHeading(__('dashboard.plugin_not_installed'));
        }

        return $table
            ->query(
                Note::withoutGlobalScopes()
                    ->where('type', 'reminder')
                    ->where('reminder_at', '>=', now())
                    ->where('reminder_sent', false)
                    ->where('user_id', auth()->id())
                    ->orderBy('reminder_at')
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('auto_title')
                    ->label(__('dashboard.table.title'))
                    ->html()
                    ->searchable(false),

                TextColumn::make('reminder_at')
                    ->label(__('dashboard.table.reminder_at'))
                    ->since()
                    ->searchable(false),

                ColorColumn::make('color_hex')
                    ->label(__('dashboard.table.color'))
                    ->copyable(false),
            ])
            ->emptyStateHeading(__('dashboard.no_upcoming_reminders'))
            ->emptyStateIcon('heroicon-o-bell-slash')
            ->paginated(false);
    }
}
