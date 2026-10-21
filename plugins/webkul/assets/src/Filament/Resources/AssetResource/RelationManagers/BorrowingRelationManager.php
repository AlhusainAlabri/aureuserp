<?php

namespace Webkul\Assets\Filament\Resources\AssetResource\RelationManagers;

use App\Support\FilamentUrl;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema as DbSchema;
use Webkul\Assets\Filament\Resources\AssetResource;
use Webkul\Assets\Models\Asset;
use Webkul\Assets\Models\AssetBorrowing;
use Webkul\Employee\Models\Employee;

class BorrowingRelationManager extends RelationManager
{
    protected static string $relationship = 'borrowings';

    public static function getTitle($ownerRecord = null, ?string $pageClass = null): string
    {
        return __('assets::assets.relations.borrowings');
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['employee', 'borrowedBy', 'returnedBy']))
            ->columns([
                TextColumn::make('employee.name')
                    ->label(__('assets::assets.fields.employee'))
                    ->sortable(),
                TextColumn::make('borrowed_at')
                    ->label(__('assets::assets.fields.borrowed_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('due_at')
                    ->label(__('assets::assets.fields.due_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('returned_at')
                    ->label(__('assets::assets.fields.returned_at'))
                    ->dateTime()
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->label(__('assets::assets.fields.borrowing_status'))
                    ->badge(),
                TextColumn::make('borrowedBy.name')
                    ->label(__('assets::assets.fields.processed_by'))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('notes')
                    ->label(__('assets::assets.fields.notes'))
                    ->placeholder('—')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('borrowed_at', 'desc')
            ->headerActions([
                $this->borrowHistoryAction(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (AssetBorrowing $record): string => FilamentUrl::appendLocaleToUrl(
                        AssetResource::getUrl('view', ['record' => $record->asset_id]),
                    )),
            ])
            ->emptyStateHeading(__('assets::assets.empty.no_borrowings'))
            ->emptyStateDescription(__('assets::assets.empty.no_borrowings_description'))
            ->emptyStateActions([
                $this->borrowHistoryAction(),
            ]);
    }

    protected function borrowHistoryAction(): Action
    {
        return Action::make('borrow')
            ->label(__('assets::assets.actions.borrow'))
            ->icon('heroicon-o-plus-circle')
            ->color('primary')
            ->visible(fn (): bool => $this->canBorrowOwnerAsset())
            ->modalHeading(__('assets::assets.actions.borrow'))
            ->schema([
                Select::make('employee_id')
                    ->label(__('assets::assets.fields.employee'))
                    ->options(fn (): array => DbSchema::hasTable('employees_employees')
                        ? Employee::query()->orderBy('name')->pluck('name', 'id')->all()
                        : [])
                    ->searchable()
                    ->searchPrompt(__('assets::assets.fields.employee_search'))
                    ->preload()
                    ->required(),
                DateTimePicker::make('due_at')
                    ->label(__('assets::assets.fields.due_at'))
                    ->native(false)
                    ->seconds(false)
                    ->minDate(now())
                    ->required(),
                Textarea::make('notes')
                    ->label(__('assets::assets.fields.notes'))
                    ->rows(3)
                    ->columnSpanFull(),
            ])
            ->action(function (array $data): void {
                $asset = $this->ownerAsset();

                if (! $asset instanceof Asset) {
                    return;
                }

                AssetBorrowing::createDirectCheckout(
                    $asset,
                    (int) $data['employee_id'],
                    Carbon::parse($data['due_at']),
                    $data['notes'] ?? null,
                );

                Notification::make()
                    ->success()
                    ->title(__('assets::assets.notifications.borrowed.title'))
                    ->body(__('assets::assets.notifications.borrowed.body', [
                        'name' => $asset->name,
                    ]))
                    ->send();
            });
    }

    protected function canBorrowOwnerAsset(): bool
    {
        $asset = $this->ownerAsset();

        return $asset instanceof Asset
            && (auth()->user()?->can('borrow', $asset) ?? false);
    }

    protected function ownerAsset(): ?Asset
    {
        $record = $this->getOwnerRecord();

        return $record instanceof Asset ? $record : null;
    }
}
