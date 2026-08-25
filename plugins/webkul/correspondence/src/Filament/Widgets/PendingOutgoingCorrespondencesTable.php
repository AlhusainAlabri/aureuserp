<?php

namespace Webkul\Correspondence\Filament\Widgets;

use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Webkul\Correspondence\Filament\Resources\CorrespondenceResource;
use Webkul\Correspondence\Filament\Widgets\Concerns\HasCorrespondenceVisibility;
use Webkul\Correspondence\Models\Correspondence;

class PendingOutgoingCorrespondencesTable extends TableWidget
{
    use HasCorrespondenceVisibility;

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 7;

    protected ?string $pollingInterval = null;

    public function getTableHeading(): ?string
    {
        return __('correspondence::correspondence.dashboard.sections.pending_outgoing');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                $this->visibleCorrespondenceQuery()
                    ->outgoing()
                    ->whereIn('status', ['draft', 'pending_approval'])
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('reference_number')
                    ->label(__('correspondence::correspondence.reference_number')),
                TextColumn::make('subject')
                    ->label(__('correspondence::correspondence.subject'))
                    ->weight('bold')
                    ->wrap(),
                TextColumn::make('status')
                    ->label(__('correspondence::correspondence.status.label'))
                    ->formatStateUsing(fn ($state): string => CorrespondenceResource::statusOptions()[$state] ?? (string) $state)
                    ->badge(),
                TextColumn::make('due_date')
                    ->label(__('correspondence::correspondence.due_date'))
                    ->date(),
            ])
            ->headerActions([
                Action::make('viewAll')
                    ->label(__('correspondence::correspondence.dashboard.actions.view_all_pending'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(CorrespondenceResource::getUrl('index', ['activeTab' => 'outgoing']))
                    ->color('gray'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (Correspondence $record): string => CorrespondenceResource::getUrl('view', ['record' => $record])),
            ])
            ->emptyStateHeading(__('correspondence::correspondence.empty.no_records'))
            ->emptyStateDescription(__('correspondence::correspondence.empty.no_records_description'))
            ->paginated(false);
    }
}
