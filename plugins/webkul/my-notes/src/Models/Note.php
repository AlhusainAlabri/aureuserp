<?php

namespace Webkul\MyNotes\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Webkul\Correspondence\Models\Correspondence;
use Webkul\Meetings\Models\Meeting;
use Webkul\MyNotes\Database\Factories\NoteFactory;
use Webkul\MyNotes\Enums\NoteBoardStatus;
use Webkul\MyNotes\Support\NoteDateFormatter;
use Webkul\Project\Models\Project;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

class Note extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ulid',
        'type',
        'title',
        'body',
        'color',
        'tags',
        'is_pinned',
        'is_archived',
        'board_status',
        'board_sort',
        'reminder_at',
        'reminder_sent',
        'reminder_email_sent',
        'meeting_id',
        'project_id',
        'correspondence_id',
        'sort_order',
        'user_id',
        'company_id',
        'audio_path',
        'audio_duration_seconds',
        'audio_transcription',
    ];

    protected $casts = [
        'tags'                  => 'array',
        'is_pinned'             => 'boolean',
        'is_archived'           => 'boolean',
        'board_status'          => NoteBoardStatus::class,
        'board_sort'            => 'integer',
        'reminder_sent'         => 'boolean',
        'reminder_email_sent'   => 'boolean',
        'reminder_at'           => 'datetime',
        'audio_duration_seconds'=> 'integer',
    ];

    public const TYPES = [
        'text',
        'checklist',
        'reminder',
        'voice',
    ];

    public const COLORS = [
        'default',
        'red',
        'orange',
        'yellow',
        'green',
        'teal',
        'blue',
        'purple',
        'pink',
        'gray',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new NoteOwnerScope);
    }

    public function checklistItems(): HasMany
    {
        return $this->hasMany(NoteChecklistItem::class)->orderBy('sort_order');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(
            class_exists(Meeting::class) ? Meeting::class : self::class,
            'meeting_id'
        );
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(
            class_exists(Project::class) ? Project::class : self::class,
            'project_id'
        );
    }

    public function correspondence(): BelongsTo
    {
        return $this->belongsTo(
            class_exists(Correspondence::class) ? Correspondence::class : self::class,
            'correspondence_id'
        );
    }

    public function isText(): bool
    {
        return $this->type === 'text';
    }

    public function isChecklist(): bool
    {
        return $this->type === 'checklist';
    }

    public function isReminder(): bool
    {
        return $this->type === 'reminder';
    }

    public function isVoice(): bool
    {
        return $this->type === 'voice';
    }

    public function isOverdue(): bool
    {
        return $this->isReminder()
            && $this->reminder_at !== null
            && $this->reminder_at->isPast()
            && ! $this->reminder_sent;
    }

    public function getChecklistProgress(): array
    {
        $items = $this->relationLoaded('checklistItems')
            ? $this->checklistItems
            : $this->checklistItems()->get();

        $total = $items->count();
        $done = $items->where('is_checked', true)->count();
        $percent = $total > 0 ? round(($done / $total) * 100) : 0;

        return ['done' => $done, 'total' => $total, 'percent' => $percent];
    }

    public function getAutoTitleAttribute(): string
    {
        if (! empty($this->title)) {
            return $this->title;
        }

        return match ($this->type) {
            'checklist' => $this->getAutoChecklistTitle(),
            'reminder'  => __('my-notes::notes.auto_title.reminder', [
                'date' => $this->reminder_at !== null
                    ? NoteDateFormatter::formatDateTime($this->reminder_at)
                    : '-',
            ]),
            'voice'     => __('my-notes::notes.types.voice'),
            default     => $this->getAutoTextTitle(),
        };
    }

    protected function getAutoTextTitle(): string
    {
        if (empty($this->body)) {
            return __('my-notes::notes.auto_title.untitled');
        }

        $text = strip_tags($this->body);

        return mb_strlen($text) > 50 ? mb_substr($text, 0, 50).'…' : $text;
    }

    protected function getAutoChecklistTitle(): string
    {
        $progress = $this->getChecklistProgress();

        return __('my-notes::notes.auto_title.checklist', $progress);
    }

    public function getColorHexAttribute(): string
    {
        return match ($this->color) {
            'red'     => '#EF4444',
            'orange'  => '#F97316',
            'yellow'  => '#EAB308',
            'green'   => '#22C55E',
            'teal'    => '#14B8A6',
            'blue'    => '#3B82F6',
            'purple'  => '#A855F7',
            'pink'    => '#EC4899',
            'gray'    => '#6B7280',
            default   => '#F3F4F6',
        };
    }

    public function getStickyBackgroundAttribute(): string
    {
        return match ($this->color) {
            'red'     => '#FEE2E2',
            'orange'  => '#FFEDD5',
            'yellow'  => '#FEF9C3',
            'green'   => '#DCFCE7',
            'teal'    => '#CCFBF1',
            'blue'    => '#DBEAFE',
            'purple'  => '#F3E8FF',
            'pink'    => '#FCE7F3',
            'gray'    => '#F3F4F6',
            default   => '#FFFBEB',
        };
    }

    public function getStickyRotationAttribute(): float
    {
        $hash = crc32($this->ulid ?? (string) $this->id);

        return match ($hash % 5) {
            0       => -2.5,
            1       => -1.0,
            2       => 0.0,
            3       => 1.0,
            default => 2.5,
        };
    }

    public function getStickyBackgroundDarkAttribute(): string
    {
        return match ($this->color) {
            'red'     => '#450A0A',
            'orange'  => '#431407',
            'yellow'  => '#422006',
            'green'   => '#052E16',
            'teal'    => '#042F2E',
            'blue'    => '#172554',
            'purple'  => '#3B0764',
            'pink'    => '#500724',
            'gray'    => '#1F2937',
            default   => '#292524',
        };
    }

    public function getBoardStatusLabelAttribute(): string
    {
        return NoteBoardStatus::tryFromValue($this->board_status?->value ?? (string) $this->board_status)->getLabel();
    }

    public function hasLinkedModule(): bool
    {
        return $this->meeting_id !== null
            || $this->project_id !== null
            || $this->correspondence_id !== null;
    }

    public function getAudioUrlAttribute(): ?string
    {
        if (empty($this->audio_path)) {
            return null;
        }

        if (! Route::has('my-notes.audio.serve')) {
            return null;
        }

        return route('my-notes.audio.serve', ['ulid' => $this->ulid]);
    }

    public function getReminderStatusAttribute(): ?string
    {
        if (! $this->isReminder() || $this->reminder_at === null) {
            return null;
        }

        if ($this->reminder_sent) {
            return 'sent';
        }

        if ($this->isOverdue()) {
            return 'overdue';
        }

        return 'upcoming';
    }

    /**
     * Prepare stored body for Filament RichEditor (TipTap).
     *
     * TipTap treats JSON-parseable strings (e.g. "123") as JSON scalars, which crashes the editor.
     */
    public static function bodyForRichEditor(mixed $body): ?string
    {
        if ($body === null || $body === '') {
            return null;
        }

        if (! is_string($body)) {
            return null;
        }

        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

            if (is_array($decoded) && ($decoded['type'] ?? null) === 'doc') {
                return $body;
            }

            if (! is_array($decoded)) {
                return self::wrapPlainTextAsHtml($body);
            }
        } catch (\JsonException) {
            // Plain HTML or text — continue below.
        }

        if (! str_contains($body, '<')) {
            return self::wrapPlainTextAsHtml($body);
        }

        return $body;
    }

    public static function wrapPlainTextAsHtml(string $text): string
    {
        return '<p>'.e($text).'</p>';
    }

    public static function normalizePayload(array $data): array
    {
        $type = in_array($data['type'] ?? 'text', self::TYPES, true) ? $data['type'] : 'text';

        $payload = [
            'type'                => $type,
            'title'               => filled($data['title'] ?? null) ? $data['title'] : null,
            'body'                => in_array($type, ['text', 'reminder'], true) ? ($data['body'] ?? null) : null,
            'color'               => in_array($data['color'] ?? 'default', self::COLORS, true) ? ($data['color'] ?? 'default') : 'default',
            'tags'                => self::normalizeTags($data['tags'] ?? []),
            'is_pinned'           => (bool) ($data['is_pinned'] ?? false),
            'board_status'        => NoteBoardStatus::tryFromValue($data['board_status'] ?? null)->value,
            'board_sort'          => (int) ($data['board_sort'] ?? 0),
            'reminder_at'         => $type === 'reminder' ? ($data['reminder_at'] ?? null) : null,
            'correspondence_id'   => $data['correspondence_id'] ?? null,
            'meeting_id'          => $data['meeting_id'] ?? null,
            'project_id'          => $data['project_id'] ?? null,
            'audio_path'          => $type === 'voice' ? ($data['audio_path'] ?? null) : null,
            'audio_transcription' => $type === 'voice' ? ($data['audio_transcription'] ?? null) : null,
        ];

        if ($type !== 'reminder') {
            $payload['reminder_sent'] = false;
            $payload['reminder_email_sent'] = false;
        }

        if ($type !== 'voice') {
            $payload['audio_duration_seconds'] = null;
        }

        return $payload;
    }

    protected static function normalizeTags(mixed $tags): ?array
    {
        if (! is_array($tags)) {
            return null;
        }

        $normalized = Collection::make($tags)
            ->filter(fn (mixed $tag): bool => is_string($tag) && filled($tag))
            ->map(fn (string $tag): string => Str::limit(trim($tag), 40, ''))
            ->unique()
            ->take(10)
            ->values()
            ->all();

        return $normalized === [] ? null : $normalized;
    }

    public function scopePinned(Builder $query): Builder
    {
        return $query->where('is_pinned', true);
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('is_archived', true);
    }

    public function scopeNotArchived(Builder $query): Builder
    {
        return $query->where('is_archived', false);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_archived', false);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeWithReminders(Builder $query): Builder
    {
        return $query->where('type', 'reminder')->whereNotNull('reminder_at');
    }

    public function scopeUpcomingReminders(Builder $query): Builder
    {
        return $query->withReminders()
            ->where('reminder_at', '>=', now())
            ->where('reminder_sent', false);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $q) use ($term): void {
            $q->where('title', 'like', "%{$term}%")
                ->orWhere('body', 'like', "%{$term}%")
                ->orWhere('tags', 'like', "%{$term}%")
                ->orWhere('audio_transcription', 'like', "%{$term}%");
        });
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Note $note): void {
            $note->ulid ??= (string) Str::ulid();
            $note->user_id ??= auth()->id();
            $note->company_id ??= auth()->user()?->default_company_id ?? Company::query()->value('id');
            $note->board_status ??= NoteBoardStatus::Inbox;
        });

        static::updating(function (Note $note): void {
            if ($note->isDirty('reminder_at')) {
                $note->reminder_sent = false;
                $note->reminder_email_sent = false;
            }
        });
    }

    protected static function newFactory(): NoteFactory
    {
        return NoteFactory::new();
    }
}
