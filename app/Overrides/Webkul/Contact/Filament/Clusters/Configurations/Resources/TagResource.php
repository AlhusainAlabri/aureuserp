<?php

namespace Webkul\Contact\Filament\Clusters\Configurations\Resources;

use App\Filament\Contacts\Concerns\ProvidesContactConfigurationResourceLabels;
use Webkul\Contact\Filament\Clusters\Configurations;
use Webkul\Contact\Filament\Clusters\Configurations\Resources\TagResource\Pages\ManageTags;
use Webkul\Contact\Models\Tag;
use Webkul\Partner\Filament\Resources\TagResource as BaseTagResource;

class TagResource extends BaseTagResource
{
    use ProvidesContactConfigurationResourceLabels;

    protected static ?string $model = Tag::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static bool $shouldRegisterNavigation = true;

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = Configurations::class;

    protected static function contactConfigurationTranslationKey(): string
    {
        return 'contacts::filament/clusters/configurations/resources/tag';
    }

    public static function getNavigationLabel(): string
    {
        return __('contacts::filament/clusters/configurations/resources/tag.navigation.title');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTags::route('/'),
        ];
    }
}
