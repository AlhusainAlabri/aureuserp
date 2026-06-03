<?php

namespace App\Filament\Widgets\Employee;

use Filament\Widgets\Widget;
use Livewire\Attributes\Reactive;

class EmployeeRecordNavigationTabs extends Widget
{
    protected string $view = 'filament.widgets.employee-record-navigation-tabs';

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    /** @var array<int, array<string, mixed>> */
    #[Reactive]
    public array $primaryItems = [];

    /** @var array<int, array<string, mixed>> */
    #[Reactive]
    public array $overflowItems = [];

    /**
     * @param  array<int, array<string, mixed>>  $primaryItems
     * @param  array<int, array<string, mixed>>  $overflowItems
     */
    public function mount(array $primaryItems = [], array $overflowItems = []): void
    {
        $this->primaryItems = $primaryItems;
        $this->overflowItems = $overflowItems;
    }
}
