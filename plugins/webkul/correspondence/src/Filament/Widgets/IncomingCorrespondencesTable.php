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

class IncomingCorrespondencesTable extends TableWidget
{
    use HasCorrespondenceVisibility;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 7;

    protected ?string $pollingInterval = null;

    public function getTableHeading(): ?string
    {
        return __('correspondence::correspondence.dashboard.sections.incoming');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                $this->visibleCorrespondenceQuery()
                    ->incoming()
                    ->latest('received_at')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('reference_number')
                    ->label(__('correspondence::correspondence.reference_number')),
                TextColumn::make('sender_name')
                    ->label(__('correspondence::correspondence.sender_name')),
                TextColumn::make('subject')
                    ->label(__('correspondence::correspondence.subject'))
                    ->weight('bold')
                    ->wrap(),
                TextColumn::make('received_at')
                    ->label(__('correspondence::correspondence.received_at'))
                    ->date(),
                TextColumn::make('priority')
                    ->label(__('correspondence::correspondence.priority.label'))
                    ->formatStateUsing(fn ($state): string => CorrespondenceResource::priorityOptions()[$state] ?? (string) $state)
                    ->badge(),
            ])
            ->headerActions([
                Action::make('viewAll')
                    ->label(__('correspondence::correspondence.dashboard.actions.view_all_incoming'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(CorrespondenceResource::getUrl('index', ['activeTab' => 'incoming']))
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
