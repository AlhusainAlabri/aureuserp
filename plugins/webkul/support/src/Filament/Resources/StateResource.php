<?php

namespace Webkul\Support\Filament\Resources;

use App\Filament\Concerns\TranslatesResourceLabels;
use Filament\Resources\Resource;
use Webkul\Support\Models\State;

class StateResource extends Resource
{
    use TranslatesResourceLabels;

    protected static ?string $model = State::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static bool $isGloballySearchable = false;

    protected static ?string $recordTitleAttribute = 'name';
}
