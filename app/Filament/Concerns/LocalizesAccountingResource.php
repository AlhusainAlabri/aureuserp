<?php

namespace App\Filament\Concerns;

use App\Filament\Extensions\AccountingResourceExtensions;
use Filament\Schemas\Schema;

trait LocalizesAccountingResource
{
    use ProvidesAccountingPluralLabel;

    public static function form(Schema $schema): Schema
    {
        return AccountingResourceExtensions::localizeForm(parent::form($schema));
    }
}
