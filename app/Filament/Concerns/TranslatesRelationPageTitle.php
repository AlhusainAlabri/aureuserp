<?php

namespace App\Filament\Concerns;

use Filament\Resources\Pages\ManageRelatedRecords;
use ReflectionMethod;

/**
 * Relation pages ("receipts", "bills"…) title themselves from the relationship name ("Operations"),
 * which stays English. When the page declares a translated navigation label, use it for the title too.
 */
trait TranslatesRelationPageTitle
{
    public static function getRelationshipTitle(): string
    {
        $declaredBy = (new ReflectionMethod(static::class, 'getNavigationLabel'))->getDeclaringClass()->getName();

        if ($declaredBy !== ManageRelatedRecords::class) {
            return static::getNavigationLabel();
        }

        return __(parent::getRelationshipTitle());
    }
}
