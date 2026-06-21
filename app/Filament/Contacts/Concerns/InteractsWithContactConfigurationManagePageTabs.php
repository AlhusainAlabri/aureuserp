<?php

namespace App\Filament\Contacts\Concerns;

use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Model;

trait InteractsWithContactConfigurationManagePageTabs
{
    use InteractsWithContactConfigurationManagePage;

    /**
     * @return class-string<Model>
     */
    abstract protected static function configurationManagePageModel(): string;

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $model = static::configurationManagePageModel();

        return [
            'all' => Tab::make(static::configurationManageTrans('tabs.all'))
                ->badge($model::count()),
            'archived' => Tab::make(static::configurationManageTrans('tabs.archived'))
                ->badge($model::onlyTrashed()->count())
                ->modifyQueryUsing(fn ($query) => $query->onlyTrashed()),
        ];
    }
}
