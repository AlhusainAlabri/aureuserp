<?php

namespace Webkul\Support\Filament\Resources;

use App\Filament\Concerns\TranslatesResourceLabels;
use Filament\Resources\Resource;
use Webkul\Support\Models\Country;

class CountryResource extends Resource
{
    use TranslatesResourceLabels;

    protected static ?string $model = Country::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static bool $isGloballySearchable = false;

    protected static ?string $recordTitleAttribute = 'name';
}
