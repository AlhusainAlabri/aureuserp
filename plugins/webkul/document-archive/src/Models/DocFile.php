<?php

namespace Webkul\DocumentArchive\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Number;
use Webkul\DocumentArchive\Database\Factories\DocFileFactory;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

class DocFile extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'doc_files';

    protected $fillable = [
        'reference_number',
        'folder_id',
        'name',
        'original_filename',
        'file_path',
        'file_size',
        'mime_type',
        'extension',
        'description',
        'tags',
        'tag_color',
        'password_hash',
        'is_private',
        'expiry_date',
        'version',
        'project_id',
        'meeting_id',
        'correspondence_id',
        'view_count',
        'download_count',
        'company_id',
        'creator_id',
    ];

    protected $casts = [
        'tags'            => 'array',
        'is_private'      => 'boolean',
        'expiry_date'     => 'date',
        'view_count'      => 'integer',
        'download_count'  => 'integer',
        'version'         => 'integer',
        'file_size'       => 'integer',
    ];

    public function folder(): BelongsTo
    {
        return $this->belongsTo(DocFolder::class, 'folder_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocFileVersion::class, 'file_id')->orderByDesc('version_number');
    }

    public function shareLinks(): HasMany
    {
        return $this->hasMany(DocShareLink::class, 'file_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(DocFileActivity::class, 'file_id');
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', now()->toDateString());
    }

    public function scopeExpiringSoon(Builder $query, int $days = 7): Builder
    {
        return $query->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now()->toDateString(), now()->addDays($days)->toDateString()]);
    }

    /**
     * @param  array<int, string>  $tagNames
     */
    public function scopeWithAnyTag(Builder $query, array $tagNames): Builder
    {
        $tagNames = collect($tagNames)
            ->filter(fn (mixed $name): bool => filled($name))
            ->map(fn (mixed $name): string => (string) $name)
            ->values()
            ->all();

        if ($tagNames === []) {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($tagNames): void {
            foreach ($tagNames as $tagName) {
                $builder->orWhere(function (Builder $tagQuery) use ($tagName): void {
                    $tagQuery->whereJsonContains('tags', $tagName)
                        ->orWhere('tags', 'like', '%"name":"'.addcslashes($tagName, '%_\\').'"%')
                        ->orWhere('tags', 'like', '%"name": "'.addcslashes($tagName, '%_\\').'"%');
                });
            }
        });
    }

    public function isExpired(): bool
    {
        if (! $this->expiry_date) {
            return false;
        }

        return $this->expiry_date->lte(now()->startOfDay());
    }

    public function isExpiringSoon(?int $days = null): bool
    {
        if (! $this->expiry_date || $this->isExpired()) {
            return false;
        }

        $days ??= (int) config('document-archive.expiring_soon_days', 7);

        return $this->expiry_date->lte(now()->addDays($days)->startOfDay());
    }

    /**
     * @return 'expired'|'expiring_soon'|null
     */
    public function getExpiryStatus(): ?string
    {
        if (! $this->expiry_date) {
            return null;
        }

        if ($this->isExpired()) {
            return 'expired';
        }

        if ($this->isExpiringSoon()) {
            return 'expiring_soon';
        }

        return null;
    }

    public function getDaysUntilExpiry(): ?int
    {
        if (! $this->expiry_date) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->expiry_date->startOfDay(), false);
    }

    public function isPasswordProtected(): bool
    {
        if ($this->hasPassword()) {
            return true;
        }

        return $this->folder?->hasPassword() ?? false;
    }

    public function getFileSizeForHumans(): string
    {
        return Number::fileSize((int) $this->file_size);
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf'
            || strtolower((string) $this->extension) === 'pdf';
    }

    public function isOffice(): bool
    {
        return in_array(strtolower((string) $this->extension), [
            'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp',
        ], true);
    }

    public function isPreviewable(): bool
    {
        return $this->isImage() || $this->isPdf();
    }

    public function hasPassword(): bool
    {
        return ! empty($this->password_hash);
    }

    public function checkPassword(string $password): bool
    {
        if (! $this->hasPassword()) {
            return true;
        }

        return Hash::check($password, $this->password_hash);
    }

    public function canBeAccessedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->can('view_any_document_archive_doc::file')) {
            return true;
        }

        if ($this->creator_id === $user->id) {
            return true;
        }

        if ($this->is_private) {
            return false;
        }

        return $this->folder ? $this->folder->canBeAccessedBy($user) : true;
    }

    public function getLatestVersion(): ?DocFileVersion
    {
        return $this->versions()->first();
    }

    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    public function incrementDownloadCount(): void
    {
        $this->increment('download_count');
    }

    /**
     * @return array<int, array{name: string, color: string|null}>
     */
    public function getTagsWithColors(): array
    {
        return collect($this->tags ?? [])
            ->map(function (array|string $tag): ?array {
                if (is_string($tag)) {
                    return ['name' => $tag, 'color' => $this->tag_color];
                }

                $name = trim((string) ($tag['name'] ?? ''));

                if ($name === '') {
                    return null;
                }

                return [
                    'name'  => $name,
                    'color' => $tag['color'] ?? $this->tag_color,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    public function getFormattedTags(): string
    {
        return collect($this->getTagsWithColors())
            ->pluck('name')
            ->implode(', ');
    }

    protected static function newFactory(): DocFileFactory
    {
        return DocFileFactory::new();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (DocFile $file): void {
            $file->creator_id ??= Auth::id();
            $file->company_id ??= Auth::user()?->default_company_id;
            $file->reference_number ??= static::nextReferenceNumber(now()->year);
        });
    }

    public static function nextReferenceNumber(int $year): string
    {
        return DB::transaction(function () use ($year): string {
            $latest = static::query()
                ->where('reference_number', 'like', "DOC-{$year}-%")
                ->lockForUpdate()
                ->orderByDesc('reference_number')
                ->value('reference_number');

            $sequence = $latest ? ((int) substr($latest, -4)) + 1 : 1;

            return sprintf('DOC-%d-%04d', $year, $sequence);
        });
    }
}
