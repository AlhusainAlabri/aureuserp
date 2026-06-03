<?php

namespace App\Filament\Assets\Pages;

use App\Filament\Assets\Concerns\ConfiguresAssetBorrowingTable;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

abstract class BaseBorrowingRequestsPage extends Page implements HasTable
{
    use ConfiguresAssetBorrowingTable;
    use HasPageShield;
    use InteractsWithTable;

    protected string $view = 'filament.assets.pages.borrowing-requests';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 2;

    abstract protected function scopedQuery(): Builder;

    public static function canAccess(array $parameters = []): bool
    {
        return Schema::hasTable('asset_borrowings')
            && parent::canAccess($parameters);
    }

    public function table(Table $table): Table
    {
        return $this->configureBorrowingTable(
            $table->query(fn (): Builder => $this->scopedQuery())
        );
    }
}
