<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Widgets\Dashboard\Concerns\BuildsEmptyTableQueries;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Schema;
use Webkul\Meetings\Models\Meeting;

class UpcomingMeetingsWidget extends BaseWidget
{
    use BuildsEmptyTableQueries;
    use InteractsWithPageFilters;

    protected static ?int $sort = 10;

    protected static bool $isLazy = false;

    public function getColumnSpan(): int|string|array
    {
        return ['default' => 12, 'lg' => 6];
    }

    protected function getTableHeading(): string|Htmlable|null
    {
        return __('dashboard.widgets.upcoming_meetings');
    }

    public function table(Table $table): Table
    {
        if (! Schema::hasTable('meetings')) {
            return $table
                ->query($this->emptyTableQuery(Meeting::class, [
                    'id'           => '0',
                    'title'        => "''",
                    'type'         => "''",
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
                    ->whereBetween('meeting_date', [now(), now()->addDays(7)])
                    ->where('status', 'confirmed')
                    ->orderBy('meeting_date')
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('title')
                    ->label(__('dashboard.table.title'))
                    ->limit(40)
                    ->searchable(false),

                TextColumn::make('type')
                    ->label(__('dashboard.table.type'))
                    ->badge()
                    ->searchable(false),

                TextColumn::make('meeting_date')
                    ->label(__('dashboard.table.date'))
                    ->dateTime('D, d M · h:i A')
                    ->searchable(false),

                TextColumn::make('location')
                    ->label(__('dashboard.table.location'))
                    ->limit(25)
                    ->searchable(false),
            ])
            ->emptyStateHeading(__('dashboard.no_meetings_this_week'))
            ->emptyStateIcon('heroicon-o-calendar')
            ->paginated(false);
    }
}
