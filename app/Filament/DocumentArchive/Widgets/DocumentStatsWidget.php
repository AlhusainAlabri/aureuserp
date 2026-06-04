<?php

namespace App\Filament\DocumentArchive\Widgets;

use Webkul\DocumentArchive\Filament\Widgets\DocumentStatsWidget as BaseDocumentStatsWidget;

class DocumentStatsWidget extends BaseDocumentStatsWidget
{
    protected ?string $pollingInterval = null;
}
