<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Widgets\Dashboard\Concerns\BuildsEmptyTableQueries;
use App\Filament\Widgets\Dashboard\Concerns\HasOrgDashboardTableLayout;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Webkul\Correspondence\Filament\Resources\CorrespondenceResource;
use Webkul\Correspondence\Models\Correspondence;

class UnreadCorrespondenceWidget extends BaseWidget
{
    use BuildsEmptyTableQueries;
    use HasOrgDashboardTableLayout;
    use InteractsWithPageFilters;

    protected static ?int $sort = 11;

    protected static bool $isLazy = false;

    public function getTableHeading(): string|Htmlable|null
    {
        return __('dashboard.widgets.unread_correspondence');
    }

    public function table(Table $table): Table
    {
        if (! Schema::hasTable('correspondences')) {
            return $table
                ->query($this->emptyTableQuery(Correspondence::class, [
                    'id'               => '0',
                    'reference_number' => "''",
                    'subject'          => "''",
                    'sender_name'      => "''",
                    'priority'         => "''",
                    'received_at'      => 'null',
                    'deleted_at'       => 'null',
                ]))
                ->columns([TextColumn::make('subject')])
                ->emptyStateHeading(__('dashboard.plugin_not_installed'));
        }

        $user = Auth::user();

        $query = Correspondence::query()
            ->when(
                Schema::hasTable('correspondence_reads') && $user,
                fn ($query) => $query->unreadFor($user),
                fn ($query) => $query->where('direction', 'incoming')->where('status', 'received'),
            )
            ->orderByRaw("FIELD(priority, 'urgent', 'confidential', 'normal')")
            ->limit(5);

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('reference_number')
                    ->label(__('dashboard.table.reference'))
                    ->searchable(false),

                TextColumn::make('subject')
                    ->label(__('dashboard.table.subject'))
                    ->limit(35)
                    ->searchable(false),

                TextColumn::make('sender_name')
                    ->label(__('dashboard.table.sender'))
                    ->limit(20)
                    ->searchable(false),

                TextColumn::make('priority')
                    ->label(__('dashboard.table.priority'))
                    ->formatStateUsing(fn (?string $state): string => CorrespondenceResource::priorityOptions()[$state] ?? (string) $state)
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'urgent'       => 'danger',
                        'confidential' => 'warning',
                        default        => 'gray',
                    })
                    ->searchable(false),

                TextColumn::make('received_at')
                    ->label(__('dashboard.table.received_at'))
                    ->since()
                    ->searchable(false),
            ])
            ->emptyStateIcon('heroicon-o-envelope')
            ->emptyStateHeading(__('dashboard.empty.no_correspondences'))
            ->emptyStateDescription(__('dashboard.empty.no_correspondences_description'))
            ->paginated(false);
    }
}
