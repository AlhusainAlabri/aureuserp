<?php

namespace App\Filament\Assets\Concerns;

use App\Filament\Assets\Actions\ApproveBorrowingAction;
use App\Filament\Assets\Actions\RejectBorrowingAction;
use App\Filament\Assets\Resources\AssetBorrowingResource;
use App\Support\FilamentUrl;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Assets\Enums\BorrowingStatus;
use Webkul\Assets\Models\AssetBorrowing;

trait ConfiguresAssetBorrowingTable
{
    public function configureBorrowingTable(Table $table): Table
    {
        return $table
            ->query(AssetBorrowing::query()->with(['asset', 'employee', 'requestedBy']))
            ->columns([
                TextColumn::make('asset.asset_number')
                    ->label(__('assets::assets.fields.asset_number'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('asset.name')
                    ->label(__('assets::assets.fields.name'))
                    ->searchable()
                    ->wrap(),
                TextColumn::make('employee.name')
                    ->label(__('assets::assets.fields.employee'))
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->label(__('assets::assets.fields.borrowing_status'))
                    ->badge(),
                TextColumn::make('due_at')
                    ->label(__('assets::assets.fields.due_at'))
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('borrowed_at')
                    ->label(__('assets::assets.fields.borrowed_at'))
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('returned_at')
                    ->label(__('assets::assets.fields.returned_at'))
                    ->dateTime('d M Y H:i')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('assets::assets.fields.borrowing_status'))
                    ->options(BorrowingStatus::class),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (AssetBorrowing $record): string => FilamentUrl::appendLocaleToUrl(
                        AssetBorrowingResource::getUrl('view', ['record' => $record->id]),
                    )),
                ApproveBorrowingAction::makeTableAction(),
                RejectBorrowingAction::makeTableAction(),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('assets-extensions::requests.empty.heading'))
            ->emptyStateDescription(__('assets-extensions::requests.empty.description'));
    }

    protected function employeeScopedQuery(): Builder
    {
        $employeeId = auth()->user()?->employee?->id;

        return AssetBorrowing::query()
            ->with(['asset', 'employee'])
            ->when($employeeId, fn (Builder $query) => $query->where('employee_id', $employeeId), fn (Builder $query) => $query->whereRaw('1 = 0'));
    }
}
