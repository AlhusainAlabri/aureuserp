<?php

namespace Webkul\Contact\Filament\Clusters\Configurations\Resources;

use App\Filament\Contacts\Concerns\ProvidesContactConfigurationResourceLabels;
use Webkul\Contact\Filament\Clusters\Configurations;
use Webkul\Contact\Filament\Clusters\Configurations\Resources\IndustryResource\Pages\ManageIndustries;
use Webkul\Contact\Models\Industry;
use Webkul\Partner\Filament\Resources\IndustryResource as BaseIndustryResource;

class IndustryResource extends BaseIndustryResource
{
    use ProvidesContactConfigurationResourceLabels;

    protected static ?string $model = Industry::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office';

    protected static bool $shouldRegisterNavigation = true;

    protected static ?int $navigationSort = 3;

    protected static ?string $cluster = Configurations::class;

    protected static function contactConfigurationTranslationKey(): string
    {
        return 'contacts::filament/clusters/configurations/resources/industry';
    }

    public static function getNavigationLabel(): string
    {
        return __('contacts::filament/clusters/configurations/resources/industry.navigation.title');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageIndustries::route('/'),
        ];
    }
}
