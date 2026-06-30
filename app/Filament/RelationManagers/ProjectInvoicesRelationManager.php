<?php

namespace App\Filament\RelationManagers;

use App\Services\Projects\ProjectFinancialSummaryService;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Schema;
use Webkul\Account\Filament\Resources\BillResource;
use Webkul\Account\Filament\Resources\InvoiceResource;
use Webkul\Account\Models\Move;

class ProjectInvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'accountMoves';

    public static function getTitle($ownerRecord = null, ?string $pageClass = null): string
    {
        return __('projects-extensions::project-relations.invoices');
    }

    public static function canViewForRecord($ownerRecord, string $pageClass): bool
    {
        return Schema::hasTable('accounts_account_moves')
            && Schema::hasColumn('accounts_account_moves', 'project_id');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('projects-extensions::columns.reference'))
                    ->searchable(),
                TextColumn::make('move_type')
                    ->label(__('projects-extensions::columns.state'))
                    ->badge(),
                TextColumn::make('state')
                    ->badge(),
                TextColumn::make('partner.name')
                    ->label(__('projects-extensions::columns.vendor'))
                    ->placeholder('—'),
                TextColumn::make('amount_total')
                    ->label(__('projects-extensions::columns.amount'))
                    ->formatStateUsing(fn ($state): string => app(ProjectFinancialSummaryService::class)->formatOmr((float) $state))
                    ->sortable(),
                TextColumn::make('invoice_date')
                    ->label(__('projects-extensions::columns.date'))
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(function (Move $record): string {
                        return str_contains($record->move_type, 'in_')
                            ? BillResource::getUrl('view', ['record' => $record])
                            : InvoiceResource::getUrl('view', ['record' => $record]);
                    }),
            ])
            ->defaultSort('invoice_date', 'desc');
    }
}
