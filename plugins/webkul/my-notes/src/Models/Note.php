<?php

namespace Webkul\MyNotes\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\Correspondence\Models\Correspondence;
use Webkul\Meetings\Models\Meeting;
use Webkul\MyNotes\Database\Factories\NoteFactory;
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
        'reminder_sent'         => 'boolean',
        'reminder_email_sent'   => 'boolean',
        'reminder_at'           => 'datetime',
        'audio_duration_seconds'=> 'integer',
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

    public function meeting(): ?BelongsTo
    {
        if (! class_exists(Meeting::class)) {
            return null;
        }

        return $this->belongsTo(Meeting::class);
    }

    public function project(): ?BelongsTo
    {
        if (! class_exists(Project::class)) {
            return null;
        }

        return $this->belongsTo(Project::class);
    }

    public function correspondence(): ?BelongsTo
    {
        if (! class_exists(Correspondence::class)) {
            return null;
        }

        return $this->belongsTo(Correspondence::class);
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
        $total = $this->checklistItems()->count();
        $done = $this->checklistItems()->where('is_checked', true)->count();
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
            'reminder'  => __('my-notes::notes.reminder_title_auto', ['date' => $this->reminder_at?->format('d M Y h:i A') ?? '-']),
            'voice'     => __('my-notes::notes.voice_memo'),
            default     => $this->getAutoTextTitle(),
        };
    }

    protected function getAutoTextTitle(): string
    {
        if (empty($this->body)) {
            return __('my-notes::notes.untitled_note');
        }

        $text = strip_tags($this->body);

        return mb_strlen($text) > 50 ? mb_substr($text, 0, 50).'…' : $text;
    }

    protected function getAutoChecklistTitle(): string
    {
        $progress = $this->getChecklistProgress();

        return __('my-notes::notes.checklist_title_auto', $progress);
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

        return Storage::disk('local')->temporaryUrl($this->audio_path, now()->addMinutes(60));
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
            $note->company_id ??= auth()->user()?->default_company_id;
        });
    }

    protected static function newFactory(): NoteFactory
    {
        return NoteFactory::new();
    }
}
