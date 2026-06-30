<?php

namespace App\Livewire;

use Filament\Livewire\GlobalSearch as BaseGlobalSearch;
use Illuminate\Contracts\View\View;

class ModuleLauncherGlobalSearch extends BaseGlobalSearch
{
    public function render(): View
    {
        return view('filament.components.module-launcher-global-search', [
            'results' => $this->getResults(),
        ]);
    }
}
