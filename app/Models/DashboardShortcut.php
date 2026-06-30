<?php

namespace App\Models;

use Database\Factories\DashboardShortcutFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DashboardShortcut extends Model
{
    /** @use HasFactory<DashboardShortcutFactory> */
    use HasFactory;

    protected $fillable = [
        'title_en',
        'title_ar',
        'url',
        'icon',
        'color',
        'sort',
        'is_active',
        'opens_in_new_tab',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active'         => 'boolean',
            'opens_in_new_tab'  => 'boolean',
            'sort'              => 'integer',
        ];
    }

    public function getTitle(): string
    {
        if (app()->getLocale() === 'ar' && filled($this->title_ar)) {
            return $this->title_ar;
        }

        return $this->title_en;
    }

    public function getResolvedUrl(): string
    {
        if (preg_match('#^https?://#i', $this->url) || str_starts_with($this->url, '//')) {
            return $this->url;
        }

        return url($this->url);
    }
}
