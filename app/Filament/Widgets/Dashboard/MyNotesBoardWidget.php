<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Widgets\Dashboard\Concerns\HasOrgDashboardTableLayout;
use App\Support\SchemaMemo;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Webkul\MyNotes\Filament\Widgets\MyNotesBoardWidget as BaseMyNotesBoardWidget;

class MyNotesBoardWidget extends BaseMyNotesBoardWidget
{
    use HasOrgDashboardTableLayout;
    use InteractsWithPageFilters;

    protected static ?int $sort = 21;

    public static function canView(): bool
    {
        return SchemaMemo::hasTable('notes');
    }

    public function getColumnSpan(): int|string|array
    {
        return [
            'default' => 12,
            'lg'      => 6,
        ];
    }
}
