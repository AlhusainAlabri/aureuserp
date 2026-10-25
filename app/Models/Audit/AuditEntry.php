<?php

namespace App\Models\Audit;

use App\Support\Audit\AuditRecorder;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Throwable;
use Webkul\Chatter\Models\Message;

/**
 * A chatter message that records a change (it has an event). Reads through this model only see those rows.
 */
class AuditEntry extends Message
{
    public const MAX_VALUE_LENGTH = 80;

    protected static function booted(): void
    {
        static::addGlobalScope('audit-entries', fn (Builder $query) => $query->whereNotNull($query->qualifyColumn('event')));
    }

    /**
     * Best-effort link to the Filament page of the record this entry is about; null when the record is gone, has no
     * page, or the signed-in user may not open it.
     */
    public function recordUrl(): ?string
    {
        try {
            $subject = $this->messageable;

            if (! $subject instanceof Model) {
                return null;
            }

            $resource = Filament::getModelResource($subject);

            if (! $resource) {
                return null;
            }

            if ($resource::hasPage('view') && $resource::canView($subject)) {
                return $resource::getUrl('view', ['record' => $subject]);
            }

            if ($resource::hasPage('edit') && $resource::canEdit($subject)) {
                return $resource::getUrl('edit', ['record' => $subject]);
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }

    /**
     * The record type shown to users: a translation of the model name when there is one, else its headline.
     */
    public function subjectLabel(): string
    {
        return static::subjectLabelFor((string) $this->messageable_type);
    }

    public static function subjectLabelFor(string $type): string
    {
        $class = Relation::getMorphedModel($type) ?? $type;
        $basename = class_basename($class);

        if ($basename === '') {
            return __('audit.table.unknown');
        }

        $key = 'audit.subjects.'.$basename;

        return Lang::has($key) ? __($key) : Str::headline($basename);
    }

    public function eventLabel(): string
    {
        $event = (string) $this->event;
        $key = 'audit.events.'.$event;

        if (! Lang::has($key)) {
            return Str::headline($event);
        }

        return __($key, ['model' => $this->subjectLabel()]);
    }

    public static function eventNameFor(string $event): string
    {
        $key = 'audit.event_names.'.$event;

        return Lang::has($key) ? __($key) : Str::headline($event);
    }

    /**
     * Readable "label: old → new" lines built from the properties, never from the stored body.
     *
     * @return array<int, string>
     */
    public function summaryLines(): array
    {
        $lines = [];

        foreach ((array) $this->properties as $attribute => $change) {
            if (! is_array($change)) {
                continue;
            }

            $label = static::attributeLabel((string) $attribute);
            $old = static::formatValue($change['old_value'] ?? null);
            $new = static::formatValue($change['new_value'] ?? null);

            $lines[] = match ($change['type'] ?? 'modified') {
                'added'   => "{$label}: {$new}",
                'removed' => "{$label}: {$old}",
                default   => "{$label}: {$old} → {$new}",
            };
        }

        return $lines;
    }

    /**
     * Every changed field with its full old and new values, one entry per line (the permission lists hold one key
     * per line); values stay plain text and are escaped where they are shown.
     *
     * @return array<int, array{label: string, old: array<int, string>, new: array<int, string>}>
     */
    public function changeRows(): array
    {
        $rows = [];

        foreach ((array) $this->properties as $attribute => $change) {
            if (! is_array($change)) {
                continue;
            }

            $isList = in_array($attribute, [AuditRecorder::PERMISSIONS_ADDED, AuditRecorder::PERMISSIONS_REMOVED], true);

            $rows[] = [
                'label' => static::attributeLabel((string) $attribute),
                'old'   => static::fullValueLines($change['old_value'] ?? null, $isList),
                'new'   => static::fullValueLines($change['new_value'] ?? null, $isList),
            ];
        }

        return $rows;
    }

    public static function attributeLabel(string $attribute): string
    {
        $labels = [
            AuditRecorder::PERMISSIONS_ADDED   => 'audit.permissions.added',
            AuditRecorder::PERMISSIONS_REMOVED => 'audit.permissions.removed',
        ];

        $key = $labels[$attribute] ?? 'audit.attributes.'.$attribute;

        return Lang::has($key) ? __($key) : Str::headline($attribute);
    }

    protected static function formatValue(mixed $value): string
    {
        return Str::limit(static::valueAsText($value), static::MAX_VALUE_LENGTH);
    }

    /**
     * @return array<int, string>
     */
    protected static function fullValueLines(mixed $value, bool $asList): array
    {
        $text = static::valueAsText($value);

        if (! $asList || $text === __('audit.table.empty')) {
            return [$text];
        }

        return array_values(array_filter(array_map('trim', explode(',', $text)), fn (string $line): bool => $line !== ''));
    }

    protected static function valueAsText(mixed $value): string
    {
        if ($value === null || $value === '') {
            return __('audit.table.empty');
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return (string) $value;
    }
}
