<?php

namespace App\Support\Livewire;

use Livewire\ComponentHook;

/**
 * Filament warns before leaving a form whose data differs from what was last saved. Workflow buttons
 * (confirm, validate, produce…) change the record on the server and refresh the form, which made the
 * warning appear on a form nobody had edited. When the form was clean before such a request and the user
 * edited nothing in it, the refreshed data is the new "saved" state.
 */
class KeepUnsavedChangesAlertAccurate extends ComponentHook
{
    public function hydrate(): void
    {
        $component = $this->component;

        if (! $this->tracksUnsavedData($component)) {
            return;
        }

        $this->storeSet('wasClean', static::hash($component->data) === $component->savedDataHash);
    }

    public function update(string $propertyName, string $fullPath, mixed $newValue): void
    {
        if (str_starts_with($fullPath, 'data')) {
            $this->storeSet('userEditedData', true);
        }
    }

    public function call(string $method): void
    {
        $this->storeSet('calledMethod', true);
    }

    public function dehydrate(): void
    {
        $component = $this->component;

        if (! $this->tracksUnsavedData($component)
            || ! $this->storeGet('wasClean', false)
            || $this->storeGet('userEditedData', false)
            || ! $this->storeGet('calledMethod', false)) {
            return;
        }

        $component->savedDataHash = static::hash($component->data);
    }

    protected function tracksUnsavedData(mixed $component): bool
    {
        return property_exists($component, 'savedDataHash')
            && isset($component->savedDataHash)
            && property_exists($component, 'data')
            && is_array($component->data);
    }

    protected static function hash(array $data): string
    {
        return md5((string) str(json_encode($data, JSON_UNESCAPED_UNICODE))->replace('\\', ''));
    }
}
