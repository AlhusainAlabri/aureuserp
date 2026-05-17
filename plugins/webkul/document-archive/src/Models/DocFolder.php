<?php

namespace Webkul\DocumentArchive\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Webkul\DocumentArchive\Database\Factories\DocFolderFactory;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

class DocFolder extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'doc_folders';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_id',
        'color',
        'icon',
        'password_hash',
        'is_private',
        'sort_order',
        'company_id',
        'creator_id',
    ];

    protected $casts = [
        'is_private' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function files(): HasMany
    {
        return $this->hasMany(DocFile::class, 'folder_id');
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(DocFolderPermission::class, 'folder_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function getFullPath(): string
    {
        $segments = [];
        $node = $this;

        while ($node) {
            array_unshift($segments, $node->name);
            $node = $node->parent;
        }

        return implode(' / ', $segments);
    }

    public function getDepth(): int
    {
        $depth = 0;
        $node = $this->parent;

        while ($node) {
            $depth++;
            $node = $node->parent;
        }

        return $depth;
    }

    public function getBreadcrumbs(): Collection
    {
        $crumbs = collect();
        $node = $this;

        while ($node) {
            $crumbs->prepend($node);
            $node = $node->parent;
        }

        return $crumbs;
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

    public function getAllDescendants(): Collection
    {
        $descendants = collect();

        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->getAllDescendants());
        }

        return $descendants;
    }

    public function canBeAccessedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->can('view_any_document_archive_doc_folder')) {
            return true;
        }

        if (! $this->is_private) {
            return true;
        }

        if ($this->creator_id === $user->id) {
            return true;
        }

        return $this->permissions()
            ->where(function (Builder $query) use ($user): void {
                $query->where(function (Builder $q) use ($user): void {
                    $q->where('type', 'user')->where('user_id', $user->id);
                })->orWhere(function (Builder $q) use ($user): void {
                    $q->where('type', 'role')->whereIn('role_name', $user->roles->pluck('name')->all());
                });
            })
            ->whereIn('permission', ['view', 'upload', 'manage'])
            ->exists();
    }

    public function canUserUpload(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->can('create_document_archive_doc_file')) {
            return true;
        }

        return $this->permissions()
            ->where(function (Builder $query) use ($user): void {
                $query->where(function (Builder $q) use ($user): void {
                    $q->where('type', 'user')->where('user_id', $user->id);
                })->orWhere(function (Builder $q) use ($user): void {
                    $q->where('type', 'role')->whereIn('role_name', $user->roles->pluck('name')->all());
                });
            })
            ->whereIn('permission', ['upload', 'manage'])
            ->exists();
    }

    public function canUserManage(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->can('update_document_archive_doc_folder')) {
            return true;
        }

        if ($this->creator_id === $user->id) {
            return true;
        }

        return $this->permissions()
            ->where(function (Builder $query) use ($user): void {
                $query->where(function (Builder $q) use ($user): void {
                    $q->where('type', 'user')->where('user_id', $user->id);
                })->orWhere(function (Builder $q) use ($user): void {
                    $q->where('type', 'role')->whereIn('role_name', $user->roles->pluck('name')->all());
                });
            })
            ->where('permission', 'manage')
            ->exists();
    }

    protected static function newFactory(): DocFolderFactory
    {
        return DocFolderFactory::new();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (DocFolder $folder): void {
            $folder->creator_id ??= Auth::id();
            $folder->company_id ??= Auth::user()?->default_company_id;

            if (empty($folder->slug)) {
                $folder->slug = static::generateUniqueSlug($folder->name);
            }
        });
    }

    protected static function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: Str::random(8);
        $slug = $base;
        $i = 1;

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
