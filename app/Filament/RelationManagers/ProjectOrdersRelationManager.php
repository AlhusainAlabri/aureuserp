<?php

namespace App\Filament\RelationManagers;

use App\Services\Projects\ProjectFinancialSummaryService;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Schema;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\OrderResource;

class ProjectOrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    public static function getTitle($ownerRecord = null, ?string $pageClass = null): string
    {
        return __('projects-extensions::project-relations.orders');
    }

    public static function canViewForRecord($ownerRecord, string $pageClass): bool
    {
        return Schema::hasTable('purchases_orders')
            && Schema::hasColumn('purchases_orders', 'project_id');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('projects-extensions::columns.reference'))
                    ->searchable(),
                TextColumn::make('partner.name')
                    ->label(__('projects-extensions::columns.vendor'))
                    ->placeholder('—'),
                TextColumn::make('state')
                    ->label(__('projects-extensions::columns.state'))
                    ->badge(),
                TextColumn::make('total_amount')
                    ->label(__('projects-extensions::columns.amount'))
                    ->formatStateUsing(fn ($state): string => app(ProjectFinancialSummaryService::class)->formatOmr((float) $state))
                    ->sortable(),
                TextColumn::make('ordered_at')
                    ->label(__('projects-extensions::columns.date'))
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn ($record): string => OrderResource::getUrl('view', ['record' => $record])),
            ])
            ->defaultSort('ordered_at', 'desc');
    }
}
