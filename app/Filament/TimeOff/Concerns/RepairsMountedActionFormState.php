<?php

namespace App\Filament\TimeOff\Concerns;

use Filament\Tables\Contracts\HasTable;

/**
 * FullCalendar widgets cache header/modal actions in separate boot hooks.
 * InteractsWithActions boots first and tries to resolve mountedActions before
 * those caches exist, so Filament clears the open create/edit modal. Also,
 * nested Livewire updates to mountedActions.{n}.data.* can drop name/context.
 *
 * Re-caching must reset the arrays first: InteractsWithHeaderActions /
 * InteractsWithModalActions append without clearing, so calling cache twice
 * (bootMountedCalendarActions + the trait boot hooks) duplicates buttons.
 */
trait RepairsMountedActionFormState
{
    /** @var array<int, array<string, mixed>>|null */
    protected ?array $mountedActionsBeforeDataUpdate = null;

    protected function cacheHeaderActions(): void
    {
        $this->cachedHeaderActions = [];

        parent::cacheHeaderActions();
    }

    protected function cacheModalActions(): void
    {
        $this->cachedModalActions = [];

        parent::cacheModalActions();
    }

    protected function bootMountedCalendarActions(): void
    {
        if (method_exists($this, 'cacheHeaderActions')) {
            $this->cacheHeaderActions();
        }

        if (method_exists($this, 'cacheModalActions')) {
            $this->cacheModalActions();
        }

        if (filled($originallyMountedActionIndex = array_key_last($this->mountedActions ?? []))) {
            $this->originallyMountedActionIndex = $originallyMountedActionIndex;
        }

        $this->cacheTraitActions();

        if ($this instanceof HasTable) {
            return;
        }

        if (empty($this->cacheMountedActions($this->mountedActions))) {
            $this->mountedActions = [];
        }
    }

    /**
     * Trait-suffixed hooks run after InteractsWithSchemas afterStateUpdated,
     * so we can restore name/context if a live field update corrupted them.
     */
    public function updatingRepairsMountedActionFormState(string $name, mixed $value): void
    {
        if (! preg_match('/^mountedActions\.\d+\.data(\.|$)/', $name)) {
            return;
        }

        $this->mountedActionsBeforeDataUpdate = $this->mountedActions;
    }

    public function updatedRepairsMountedActionFormState(string $name, mixed $value): void
    {
        if ($this->mountedActionsBeforeDataUpdate === null) {
            return;
        }

        if (! preg_match('/^mountedActions\.(\d+)\.data(\.|$)/', $name, $matches)) {
            $this->mountedActionsBeforeDataUpdate = null;

            return;
        }

        $index = (int) $matches[1];
        $backup = $this->mountedActionsBeforeDataUpdate[$index] ?? null;
        $current = $this->mountedActions[$index] ?? null;
        $this->mountedActionsBeforeDataUpdate = null;

        if (
            ! is_array($backup)
            || ! is_array($current)
            || blank($backup['name'] ?? null)
            || filled($current['name'] ?? null)
        ) {
            return;
        }

        $this->mountedActions[$index] = [
            ...$backup,
            'data' => [
                ...($backup['data'] ?? []),
                ...($current['data'] ?? []),
            ],
        ];
    }
}
