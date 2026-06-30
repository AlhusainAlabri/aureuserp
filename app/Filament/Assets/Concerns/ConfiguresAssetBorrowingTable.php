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
use Illuminate\Support\Carbon;
use Webkul\Assets\Enums\BorrowingStatus;
use Webkul\Assets\Models\AssetBorrowing;

trait ConfiguresAssetBorrowingTable
{
    public function configureBorrowingTable(Table $table): Table
    {
        return $table
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
                    ->formatStateUsing(fn ($state): ?string => static::formatBorrowingDateTime($state))
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('borrowed_at')
                    ->label(__('assets::assets.fields.borrowed_at'))
                    ->formatStateUsing(fn ($state): ?string => static::formatBorrowingDateTime($state))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('returned_at')
                    ->label(__('assets::assets.fields.returned_at'))
                    ->formatStateUsing(fn ($state): ?string => static::formatBorrowingDateTime($state))
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
            ->with(['asset', 'employee', 'requestedBy'])
            ->when($employeeId, fn (Builder $query) => $query->where('employee_id', $employeeId), fn (Builder $query) => $query->whereRaw('1 = 0'));
    }

    public static function formatBorrowingDateTime(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $date = $value instanceof \DateTimeInterface
            ? Carbon::instance($value)
            : Carbon::parse($value);

        if ($date->year < 1) {
            return null;
        }

        return $date->locale(app()->getLocale())->translatedFormat('j F Y H:i');
    }
}
