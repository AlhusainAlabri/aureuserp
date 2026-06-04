<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Widgets\Dashboard\Concerns\BuildsEmptyTableQueries;
use App\Filament\Widgets\Dashboard\Concerns\HasOrgDashboardTableLayout;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Schema;
use Webkul\Meetings\Models\Meeting;

class MyUpcomingMeetingsWidget extends BaseWidget
{
    use BuildsEmptyTableQueries;
    use HasOrgDashboardTableLayout;
    use InteractsWithPageFilters;

    protected static ?int $sort = 19;

    protected static bool $isLazy = true;

    public function getTableHeading(): string|Htmlable|null
    {
        return __('dashboard.widgets.my_meetings');
    }

    public function table(Table $table): Table
    {
        if (! Schema::hasTable('meetings') || ! Schema::hasTable('meeting_attendees')) {
            return $table
                ->query($this->emptyTableQuery(Meeting::class, [
                    'id'           => '0',
                    'title'        => "''",
                    'meeting_date' => 'null',
                    'location'     => "''",
                    'deleted_at'   => 'null',
                ]))
                ->columns([TextColumn::make('title')])
                ->emptyStateHeading(__('dashboard.plugin_not_installed'));
        }

        return $table
            ->query(
                Meeting::query()
                    ->whereHas('attendees', fn ($q) => $q->where('user_id', auth()->id()))
                    ->where('meeting_date', '>=', now())
                    ->where('status', 'confirmed')
                    ->orderBy('meeting_date')
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('title')
                    ->label(__('dashboard.table.title'))
                    ->limit(30)
                    ->searchable(false),

                TextColumn::make('meeting_date')
                    ->label(__('dashboard.table.date'))
                    ->dateTime('d M · h:i A')
                    ->searchable(false),

                TextColumn::make('location')
                    ->label(__('dashboard.table.location'))
                    ->limit(20)
                    ->searchable(false),
            ])
            ->emptyStateHeading(__('dashboard.no_meetings_this_week'))
            ->emptyStateIcon('heroicon-o-calendar')
            ->paginated(false);
    }
}
