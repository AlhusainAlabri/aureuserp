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

class CorrespondenceApprovalsTable extends TableWidget
{
    use HasCorrespondenceVisibility;

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 5;

    protected ?string $pollingInterval = null;

    public function getTableHeading(): ?string
    {
        return __('correspondence::correspondence.dashboard.sections.my_approvals');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                $this->pendingApprovalsQuery()
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('reference_number')
                    ->label(__('correspondence::correspondence.reference_number')),
                TextColumn::make('subject')
                    ->label(__('correspondence::correspondence.subject'))
                    ->wrap(),
                TextColumn::make('creator.name')
                    ->label(__('correspondence::correspondence.creator')),
            ])
            ->headerActions([
                Action::make('viewAll')
                    ->label(__('correspondence::correspondence.dashboard.actions.view_all_pending'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(CorrespondenceResource::getUrl('index', ['activeTab' => 'outgoing', 'tableFilters' => ['status' => ['value' => 'pending_approval']]]))
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
